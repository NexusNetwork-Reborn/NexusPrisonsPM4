<?php
declare(strict_types=1);

namespace core\player;

use core\game\item\types\vanilla\Pickaxe;
use core\game\slotbot\SlotBotRewardsSession;
use core\level\LevelManager;
use core\Nexus;
use core\provider\event\PlayerLoadEvent;
use libs\utils\Task;
use libs\utils\Utils;
use pocketmine\block\Block;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\permission\DefaultPermissions;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class PlayerListener implements Listener {

    /** @var Nexus */
    private $core;

    /** @var int[] */
    protected $times = [];

    /** @var int[] */
    protected $lastMoved = [];

    /**
     * PlayerListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority HIGHEST
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $event->setJoinMessage("");
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        SlotBotRewardsSession::addRewardSession($player->getName(), new SlotBotRewardsSession());
        $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 100000000, 0, false));
        $player->getEffects()->add(new EffectInstance(VanillaEffects::MINING_FATIGUE(), 10000000, 0, false));
        $this->core->getPlayerManager()->getLoadQueue()->addToQueue($player);
        $player->sendMessage(" ");
        $player->sendMessage(Utils::centerAlignText(TextFormat::WHITE . "Welcome " . TextFormat::GOLD . TextFormat::BOLD . $player->getName() . TextFormat::RESET . ", to " . TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "nexus" . TextFormat::GOLD . "Prison", 58));
        $player->sendMessage(Utils::centerAlignText(TextFormat::GRAY . TextFormat::ITALIC . "Space-Age confinement, Minecraft Prisons", 58));
        $player->sendMessage(Utils::centerAlignText(TextFormat::BOLD . TextFormat::RED . "SHOP: " . TextFormat::RESET . TextFormat::WHITE . "nexusprisons.tebex.io", 58));
        $player->sendMessage(Utils::centerAlignText(TextFormat::BOLD . TextFormat::BLUE . "DISCORD: " . TextFormat::RESET . TextFormat::WHITE . "discord.gg/PTenSKknBF", 58));
        $player->sendMessage(Utils::centerAlignText(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "RECOMMENDED VERSION: " . TextFormat::RESET . TextFormat::WHITE . ProtocolInfo::MINECRAFT_VERSION, 58));
        $player->sendMessage(" ");
        if(!$player->hasPlayedBefore()) {
            /** @var Pickaxe $pickaxe */
            $pickaxe = ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE, 0, 1);
            $player->getInventory()->addItem($pickaxe);
            $pos = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("extras.first-spawn-position"), Server::getInstance()->getWorldManager()->getDefaultWorld())->asVector3();
            $player->teleport($pos);
            $player->getEffects()->add(new EffectInstance(VanillaEffects::HASTE(), 36000, 1, false));
        } //else if($player->getWorld()->getFolderName() === "executive") {
//            $player->teleport($this->core->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
//        }
        $player->setCooldown(NexusPlayer::POWERBALL);
    }

    /**
     * @priority LOWEST
     *
     * @param PlayerLoadEvent $event
     */
    public function onPlayerLoad(PlayerLoadEvent $event): void {
        $world = Server::getInstance()->getWorldManager()->getDefaultWorld();
        if($world->getTime() !== 14000) {
            $world->setTime(14000);
            $world->stopTime();
        }
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $server = $this->core->getServer();
        $players = count($server->getOnlinePlayers()) - 1;
        $maxPlayers = $this->core->getServer()->getMaxPlayers();
        if($players >= ($maxPlayers - Nexus::EXTRA_SLOTS)) {
            $player = $event->getPlayer();
            if((!$player->hasPermission("permission.join.full")) and $players < ($maxPlayers + Nexus::EXTRA_SLOTS)) {
                $player->kickDelay("Server is full!");
                return;
            }
        }
        /** @var NexusPlayer $player */
        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            if(!$onlinePlayer->isLoaded()) {
                continue;
            }
            $onlinePlayer->getDataSession()->updateNameTag([$player]);
        }
        $player->getDataSession()->updateNameTag();
        $this->times[$event->getPlayer()->getUniqueId()->toString()] = time();
        $session = $player->getDataSession();
        $lastQuestReroll = $session->getLastQuestReroll();
        if((time() - $lastQuestReroll) >= 604800) {
            $session->rerollQuests();
        }
    }

    /**
     * @priority HIGHEST
     *
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $event->setQuitMessage("");
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($player->isLoaded()) {
            $session = $player->getDataSession();
            $uuid = $player->getUniqueId()->toString();
            if(isset($this->lastMoved[$uuid])) {
                $diff = time() - $this->lastMoved[$uuid];
                if(time() - $this->lastMoved[$uuid] >= 300) {
                    $this->times[$uuid] = $this->times[$uuid] + $diff;
                }
                unset($this->lastMoved[$uuid]);
            }
            if(isset($this->times[$uuid])) {
                $old = $session->getOnlineTime();
                $session->setOnlineTime($old + (time() - $this->times[$uuid]));
                unset($this->times[$uuid]);
            }
            if($player->getBossBar()->isSpawned()) {
                $player->getBossBar()->despawn();
            }
            $session->saveData();
            $this->core->getPlayerManager()->setCESession($player->getCESession());
        }
    }

    /**
     * @priority LOWEST
     *
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            $event->cancel();
            return;
        }
        $block = $event->getBlock();
        if($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
            $progressFactor = 1;
            $pos = $block->getPosition();
            $progress = $player->getBlockBreakProgress($pos);
            if($progress !== null) {
                if($progress[0] == $block) {
                    $progressFactor = $progress[1];
                    $player->setBlockBreakFactor($progressFactor);
                }
                $player->removeBlockBreakProgress($pos);
            }
            $this->core->getScheduler()->scheduleRepeatingTask(new class($player, $block, $progressFactor) extends Task {

                /** @var NexusPlayer */
                private $player;

                /** @var Block */
                private $block;

                /** @var int */
                private $time, $actualTime;

                /**
                 *  constructor.
                 *
                 * @param NexusPlayer $player
                 * @param Block $block
                 * @param float $progressFactor
                 */
                public function __construct(NexusPlayer $player, Block $block, float $progressFactor) {
                    $this->player = $player;
                    $this->block = $block;
                    $this->time = PlayerManager::calculateBlockBreakTime($player, $block) - 1;
                    $this->actualTime = (int)($this->time * $progressFactor);
                }

                /**
                 * @param int $currentTick
                 */
                public function onRun(): void {
                    if($this->player->isClosed()) {
                        $this->player->clearBlockBreakProgress();
                        $this->cancel();
                        return;
                    }
                    if($this->player->isBreaking() === true and $this->player->getBlock()->getPosition()->equals($this->block->getPosition())) {
                        if($this->actualTime-- > 0) {
                            return;
                        }
                        $item = $this->player->getInventory()->getItemInHand();
                        $this->player->getWorld()->useBreakOn($this->block->getPosition(), $item, $this->player, true);
                        $this->cancel();
                    } else {
                        if($this->time > 0) {
                            $this->player->setBlockBreakProgress($this->block->getPosition(), $this->block, $this->actualTime / $this->time);
                        } else {
                            $this->player->setBlockBreakProgress($this->block->getPosition(), $this->block, $this->actualTime);
                        } // TODO: Thonk
                        $this->player->setBlockBreakFactor();
                        $this->cancel();
                    }
                }
            }, 1);
        }
    }

    /**
     * @priority NORMAL
     *
     * @param PlayerCreationEvent $event
     */
    public function onPlayerCreation(PlayerCreationEvent $event): void {
        $event->setPlayerClass(NexusPlayer::class);
    }

    /**
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove2(PlayerMoveEvent $event) {
        $world = $event->getPlayer()->getWorld();
        if($world->getTime() !== 14000) {
            $world->setTime(14000);
            $world->stopTime();
        }
        $to = $event->getTo();
        $from = $event->getFrom();
        $uuid = $event->getPlayer()->getUniqueId()->toString();
        if($to->getYaw() !== $from->getYaw() or $to->getPitch() !== $from->getPitch()) {
            if(isset($this->lastMoved[$uuid])) {
                $diff = (time() - $this->lastMoved[$uuid]) - 300;
                if($diff >= 300 && isset($this->times[$uuid])) {
                    $this->times[$uuid] = $this->times[$uuid] + $diff;
                }
//                else {
//                    $this->lastMoved[$uuid] = time();
//                }
            }
            $this->lastMoved[$uuid] = time();
        }
    }

    /**
     * @param CraftItemEvent $event
     */
    public function onCraftItem(CraftItemEvent $event): void {
        $player = $event->getPlayer();
        if(!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $event->cancel();
        }
    }

    /**
     * @param DataPacketReceiveEvent $event
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        //if($packet instanceof EmotePacket) {
        //    $emoteId = $packet->getEmoteId();
        //    $this->core->getServer()->broadcastPackets($player->getViewers(), [EmotePacket::create($player->getId(), $emoteId, 1 << 0)]);
        //}
        /*if($packet instanceof PlayerActionPacket) {
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $action = $packet->action;
            if($action === PlayerAction::START_BREAK) {
                $player->setBreaking();
                $pos = new Vector3($packet->blockPosition->getX(), $packet->blockPosition->getY(), $packet->blockPosition->getZ());
                $block = $player->getWorld()->getBlock($pos);
                $player->setBlock($block);
            }
            if($action === PlayerAction::ABORT_BREAK or $action === PlayerAction::STOP_BREAK) {
                $player->setBreaking(false);
                $player->setBlock();
                $player->setBlockBreakFactor();
            }
        }*/
        if($packet instanceof PlayerAuthInputPacket && $packet->getBlockActions() !== null) { //TODO: test
            foreach ($packet->getBlockActions() as $action) {
                if($action instanceof PlayerBlockActionWithBlockInfo) {
                    if ($action->getActionType() === PlayerAction::START_BREAK) {
                        $player->setBreaking();
                        $pos = $action->getBlockPosition();
                        $pos = new Vector3($pos->getX(), $pos->getY(), $pos->getZ());
                        $block = $player->getWorld()->getBlock($pos);
                        $player->setBlock($block);
                    } else if($action->getActionType() === PlayerAction::ABORT_BREAK or $action->getActionType() === PlayerAction::STOP_BREAK) {
                        $player->setBreaking(false);
                        $player->setBlock();
                        $player->setBlockBreakFactor();
                    }
                }
            }
        }
    }
}
