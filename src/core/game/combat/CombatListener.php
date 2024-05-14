<?php

declare(strict_types = 1);

namespace core\game\combat;

use core\command\task\TeleportTask;
use core\game\boss\task\BossSummonTask;
use core\game\combat\guards\BaseGuard;
use core\game\combat\guards\Guard;
use core\game\combat\task\KillLogTask;
use core\game\item\ItemManager;
use core\game\item\mask\Mask;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Sword;
use core\level\entity\types\Lightning;
use core\level\entity\types\PummelBlock;
use core\level\LevelManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\ItemIds;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class CombatListener implements Listener {

    /** @var int[] */
    public $enderPearlCooldown = [];

    /** @var Nexus */
    private $core;

    /**
     * CombatListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     * @param PlayerCommandPreprocessEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $rankId = $player->getDataSession()->getRank()->getIdentifier();
        if($rankId >= Rank::TRAINEE and $rankId <= Rank::EXECUTIVE) {
            return;
        }
        if(strpos($event->getMessage(), "/") !== 0) {
            return;
        }
        if($player->isTagged()) {
            $player->sendMessage(Translation::getMessage("noPermissionCombatTag"));
            $event->cancel();
        }
    }

    /**
     * @priority LOW
     * @param PlayerItemConsumeEvent $event
     */
    public function onPlayerItemConsume(PlayerItemConsumeEvent $event) {
        /** @var NexusPlayer $player */
        $player = $event->getPlayer();
        $item = $event->getItem();
        if($item->getId() === ItemIds::ENCHANTED_GOLDEN_APPLE) {
            $player->playErrorSound();
            $event->cancel();
            return;
        }
        if($item->getId() === ItemIds::GOLDEN_APPLE) {
            $player->playErrorSound();
            $event->cancel();
            return;
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerRespawnEvent $event
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event) {
        $player = $event->getPlayer();
        $level = $player->getServer()->getWorldManager()->getDefaultWorld();
        $spawn = $level->getSpawnLocation();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $this->core->getScheduler()->scheduleDelayedTask(new class($player, $spawn) extends Task {

            /** @var NexusPlayer */
            private $player;

            /** @var Position */
            private $position;

            /**
             *  constructor.
             *
             * @param NexusPlayer $player
             * @param Position $position
             */
            public function __construct(NexusPlayer $player, Position $position) {
                $this->player = $player;
                $this->position = $position;
            }

            /**
             * @param int $currentTick
             */
            public function onRun(): void {
                if(!$this->player->isClosed()) {
                    $this->player->combatTag(false);
                    $this->player->teleport($this->position);
                    $this->player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 100000000, 0, false));
                    $this->player->getEffects()->add(new EffectInstance(VanillaEffects::MINING_FATIGUE(), 10000000, 0, false));
                }
            }
        }, 1);
    }

    /**
     * @priority LOW
     * @param PlayerDeathEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        } else {
            $player->getDataSession()->setGuardsKilled(0);
            // TODO: Message for criminal record reset?
        }
        $cause = $player->getLastDamageCause();
        $message = Translation::getMessage("death", [
            "name" => TextFormat::AQUA . $player->getName(),
        ]);
        if($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            if($killer instanceof Lightning) {
                $killer = $killer->getOwningEntity();
            }
            if($killer instanceof PummelBlock) {
                $killer = $killer->getOwningEntity();
            }
            if($killer instanceof NexusPlayer) {
                $killer->getDataSession()->addKills();
                $item = $killer->getInventory()->getItemInHand();
                $name = $item->hasCustomName() ? $item->getCustomName() : $item->getName();
                if($item->isNull()) {
                    $name = "punch";
                }
                $message = Translation::getMessage("deathByPlayer", [
                    "name" => TextFormat::AQUA . $player->getName(),
                    "killer" => TextFormat::RED . $killer->getName(),
                    "item" => $name
                ]);
                $killer->sendMessage($message);
            }
            if($killer instanceof BaseGuard) {
                $message = Translation::getMessage("deathByPlayer", [
                    "name" => TextFormat::AQUA . $player->getName(),
                    "killer" => TextFormat::RED . $killer->getNameTag(),
                    "item" => "weapon"
                ]);
            }
        }
        $player->sendMessage($message);
        $player->getCESession()->reset();
        $player->combatTag(false);
        $event->setDeathMessage("");
        $this->core->getServer()->getAsyncPool()->submitTaskToWorker(new KillLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()) . TextFormat::clean($message)), 1);
    }

    /**
     * @priority NORMAL
     * @param PlayerMoveEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $to = $event->getTo();
        $areas = $this->core->getServerManager()->getAreaManager()->getAreasInPosition($to);
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isTagged()) {
            return;
        }
        if($areas === null) {
            return;
        }
        foreach($areas as $area) {
            if($area->getPvpFlag() === false) {
                $event->cancel();
                $player->sendMessage(Translation::getMessage("enterSafeZoneInCombat"));
                return;
            }
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($player->isTagged()) {
            $player->setHealth(0);
        }
    }

    /**
     * @priority HIGH
     * @param PlayerInteractEvent $event
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event) {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $item = $event->getItem();
        if($item->getId() === ItemIds::ENDER_PEARL) {
            if(!isset($this->enderPearlCooldown[$player->getUniqueId()->toString()])) {
                $this->enderPearlCooldown[$player->getUniqueId()->toString()] = time();
                return;
            }
            if(time() - $this->enderPearlCooldown[$player->getUniqueId()->toString()] < 10) {
                $event->cancel();
                return;
            }
            $this->enderPearlCooldown[$player->getUniqueId()->toString()] = time();
            return;
        }
    }

    /**
     * @priority NORMAL
     * @param EntityDamageEvent $event
     *
     * @throws TranslationException
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $entity = $event->getEntity();
        if($entity instanceof NexusPlayer) {
            if($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
                if(!$entity->isTagged()) {
                    $event->cancel();
                }
                return;
            }
            if($event instanceof EntityDamageByEntityEvent and ($event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK or $event->getCause() === EntityDamageEvent::CAUSE_PROJECTILE)) {
                $damager = $event->getDamager();
                /** @var Armor $helm */
                $helm = $entity->getArmorInventory()->getHelmet();
                if($helm instanceof Armor && $helm->hasMask(Mask::CACTUS) && mt_rand(1, 20) === 1) {
                    $damager->attack(new EntityDamageByEntityEvent($entity, $damager, EntityDamageEvent::CAUSE_CONTACT, 1));
                }
                if(!$damager instanceof NexusPlayer) {
                    return;
                }
                $item = $damager->getInventory()->getItemInHand();
                if($event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK and (!$item instanceof Sword) and (!$item instanceof Axe)) {
                    $damager->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "You can only attack with weapons");
                    $event->cancel();
                    $damager->playErrorSound();
                    return;
                }
                if(!ItemManager::canUseTool($damager, $item)) {
                    $level = ItemManager::getLevelToUseTool($item);
                    $damager->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "You must be level $level");
                    $event->cancel();
                    $damager->playErrorSound();
                    return;
                }
                if($entity->getGuardTax() > 0) {
                    $bb = $entity->getBoundingBox()->expandedCopy(12, 12, 12);
                    $level = $entity->getWorld();
                    if($level !== null) {
                        $targeted = false;
                        foreach($level->getNearbyEntities($damager->getBoundingBox()->expandedCopy(25, 25, 25)) as $e) {
                            if($e instanceof BaseGuard) {
                                $target = $e->getTarget();
                                if($target !== null) {
                                    if($target->getName() === $damager->getName()) {
                                        $targeted = true;
                                        break;
                                    }
                                }
                            }
                        }
                        if(!$targeted) {
                            $guards = Nexus::getInstance()->getGameManager()->getCombatManager()->getNearbyGuards($bb, $level);
                            /** @var null|Guard $nearest */
                            $nearest = null;
                            $pos = $entity->getPosition();
                            foreach($guards as $guard) {
                                if($nearest === null) {
                                    $nearest = $guard;
                                    continue;
                                }
                                if($nearest->getStation()->distance($pos) > $guard->getStation()->distance($pos)) {
                                    $nearest = $guard;
                                }
                            }
                            if($nearest !== null) {
                                $damager->sendTitleTo(TextFormat::RED . TextFormat::BOLD . "(!) WARNING", TextFormat::RED . "Guards will kill you now!");
                                $damager->playErrorSound();
                                $nearest->spawnRealTo($damager);
                            }
                        }
                    }
                }
                if($entity->isTagged()) {
                    $entity->combatTag();
                }
                else {
                    $entity->combatTag();
                    $entity->sendMessage(Translation::getMessage("combatTag"));
                }
                if($damager->isTagged()) {
                    $damager->combatTag();
                }
                else {
                    $damager->combatTag();
                    $damager->sendMessage(Translation::getMessage("combatTag"));
                }
                if($entity->isFlying() === true or $entity->getAllowFlight() === true) {
                    $entity->setFlying(false);
                    $entity->setAllowFlight(false);
                    $entity->sendMessage(Translation::getMessage("flightToggle"));
                }
                if($damager->isFlying() === true or $damager->getAllowFlight() === true) {
                    $damager->setFlying(false);
                    $damager->setAllowFlight(false);
                    $damager->sendMessage(Translation::getMessage("flightToggle"));
                }
                $event->setKnockBack($event->getKnockBack() * 0.95);
            }
        }
    }

    /**
     * @priority HIGH
     * @param EntityTeleportEvent $event
     *
     * @throws TranslationException
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void {
        $entity = $event->getEntity();
        if(!$entity instanceof NexusPlayer) {
            return;
        }
        if(!$entity->isTagged()) {
            return;
        }
        $to = $event->getTo();
        if($to->getWorld() === null) {
            return;
        }
        $areas = $this->core->getServerManager()->getAreaManager()->getAreasInPosition($to);
        if($areas === null) {
            return;
        }
        foreach($areas as $area) {
            if($area->getPvpFlag() === false) {
                $event->cancel();
                $entity->sendMessage(Translation::getMessage("enterSafeZoneInCombat"));
            }
        }
    }

    // BOSS HANDLING EVENTS BEYOND THIS POINT

    public function onBossCommandEvent(PlayerCommandPreprocessEvent $event){
        /** @var NexusPlayer $player */
        $player = $event->getPlayer();
        if(BossSummonTask::getBossFight() !== null && str_starts_with($event->getMessage(), "/")){
            if(BossSummonTask::getBossFight()->inArena($player) && BossSummonTask::getBossFight()->isStarted()){
                $event->cancel();
                $event->getPlayer()->sendMessage(Translation::getMessage("commandDisabledDuringBoss"));
            }
        }
    }

    /**
     * @priority HIGH
     * @param PlayerDeathEvent $event
     */
    public function onKMOTHDeath(PlayerDeathEvent $event)
    {
        /** @var NexusPlayer $player */
        $player = $event->getPlayer();
        if ($player->getWorld()->getFolderName() === LevelManager::getSetup()->getNested("koth.world")) {
            $event->setKeepInventory(true);
            // TODO: Message?
        }
    }

//    /**
//     * @priority HIGH
//     * @param PlayerDeathEvent $event
//     */
//    public function onBossDeath(PlayerDeathEvent $event){
//        /** @var NexusPlayer $player */
//        $player = $event->getPlayer();
//        if(BossSummonTask::getBossFight() !== null && BossSummonTask::getBossFight()->inArena($player)){
//            $event->setKeepInventory(true);
//            BossSummonTask::getBossFight()->removePlayer($player);
//            $event->getPlayer()->sendMessage(Translation::getMessage("defeatedByHades"));
//        }
//    }

    public function onBossQuit(PlayerQuitEvent $event) {
        /** @var NexusPlayer $player */
        $player = $event->getPlayer();
        if(BossSummonTask::getBossFight() !== null && BossSummonTask::getBossFight()->inArena($player)){
            $player->combatTag(false); // No combat logging in boss fights
            BossSummonTask::getBossFight()->removePlayer($player);
        }
    }

    public function onBossRejoin(PlayerJoinEvent $event) {
        // Don't get left behind in the boss world lol
        $player = $event->getPlayer();
        $invalid = ["executive", LevelManager::getSetup()->getNested("boss.world")];
        if(in_array($player->getWorld()->getFolderName(), $invalid)) {
            $level = $player->getServer()->getWorldManager()->getDefaultWorld();
            if($level === null) {
                return;
            }
            $spawn = $level->getSpawnLocation();
            $player->teleport($spawn);
        }
    }

    public function onBossDamage(EntityDamageByEntityEvent $event){
        $worldName = LevelManager::getSetup()->getNested("boss.world");
        if($event->getDamager() !== null && $event->getDamager()->getWorld()->getFolderName() === $worldName) {
            $dmg = $event->getDamager();
            $victim = $event->getEntity();
            if (BossSummonTask::getBossFight() !== null && $dmg instanceof NexusPlayer && !BossSummonTask::getBossFight()->inArena($dmg)) {
                //if (BossSummonTask::getBossFight() !== null && BossSummonTask::getBossFight()->isStarted() && !BossSummonTask::getBossFight()->inArena($dmg)){
                $event->cancel();
                $dmg->sendMessage(Translation::getMessage("notInBossFight"));
                //}
            }
            if ($dmg instanceof NexusPlayer && $victim instanceof NexusPlayer) {
                //if (BossSummonTask::getBossFight() !== null && BossSummonTask::getBossFight()->isStarted() && !BossSummonTask::getBossFight()->inArena($victim)){
                    //Non-participants of the boss fight, within the boss fight arena, cannot be attacked by other entities during the boss fight.
                $event->cancel();
                $dmg->sendMessage(Translation::getMessage("bossPvPDisabled"));
                //}
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        if($event->getPlayer()->getWorld()->getFolderName() === "badlands" && $event->getBlock()->getId() === BlockLegacyIds::CHEST) {
            $event->cancel();
        }
    }
}