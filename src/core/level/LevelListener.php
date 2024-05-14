<?php
declare(strict_types=1);

namespace core\level;

use core\command\inventory\WarpMenuInventory;
use core\game\boss\task\BossSummonTask;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\enchantment\types\pickaxe\TransfuseEnchantment;
use core\game\item\event\EarnEnergyEvent;
use core\game\item\ItemManager;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Pickaxe;
use core\game\plots\PlotManager;
use core\game\wormhole\entity\ExecutiveEnderman;
use core\level\block\Meteor;
use core\level\block\Ore;
use core\level\task\CrudeOreRegenerateTask;
use core\level\task\MeteoriteRegenerateTask;
use core\level\task\RushOreRegenerateTask;
use core\level\task\SpawnRushOreTask;
use core\level\tile\CrudeOre;
use core\level\tile\Meteorite;
use core\level\tile\OreGenerator;
use core\level\tile\RushOreBlock;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\task\ExecutiveMineHeartbeatTask;
use core\player\vault\forms\VaultListForm;
use core\translation\TranslationException;
use libs\utils\UtilsException;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\permission\DefaultPermissions;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class LevelListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * LevelListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }


    /**
     * @priority HIGHEST
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteract2(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($block->getId() === BlockLegacyIds::ENDER_CHEST) {
            $event->cancel();
            $player->sendForm(new VaultListForm($player));
            //$player->sendMessage(TextFormat::RED . "Ender chests are disabled! An alternative is /pv!");
            return;
        }
        if($event->isCancelled()) {
            return;
        }
        $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
        $item = $event->getItem();
        if($tile instanceof OreGenerator) {
            $tileOre = $tile->getOre();
            if($tileOre === null or $tileOre === -1) {
                $event->cancel();
                return;
            }
            if(\core\game\item\types\custom\OreGenerator::isInstanceOf($item)) {
                $generator = \core\game\item\types\custom\OreGenerator::fromItem($item);
                $ore = $generator->getOre()->getId();
                $stack = $tile->getStack();
                $add = 1;
                if($player->isSneaking()) {
                    $add = $item->getCount();
                }
                switch($player->getWorld()->getFolderName()) {
                    case "citizen":
                        $max = 12;
                        break;
                    case "merchant":
                        $max = 16;
                        break;
                    case "king":
                        $max = 20;
                        break;
                    default:
                        $max = 256;
                        break;
                }
                if($add + $stack > $max) {
                    $add = $max - $stack;
                }
                if($tileOre === $ore and $tile->getStack() < $max) {
                    $stack += $add;
                    $tile->setStack($stack);
                    $player->getInventory()->setItemInHand($item->setCount($item->getCount() - $add));
                    $event->cancel();
                }
                $ore = BlockFactory::getInstance()->get($tileOre, 0);
                $color = ItemManager::getColorByOre($ore);
                $player->sendAlert(TextFormat::GOLD . "There are currently " . $color . TextFormat::BOLD . $ore->getName() . " Generators" . TextFormat::GOLD . " * " . TextFormat::WHITE . $stack . TextFormat::RESET . TextFormat::GOLD . " pumping ores.");
            }
            else {
                $ore = BlockFactory::getInstance()->get($tileOre, 0);
                $stack = $tile->getStack();
                $color = ItemManager::getColorByOre($ore);
                $player->sendAlert(TextFormat::GOLD . "There are currently " . $color . TextFormat::BOLD . $ore->getName() . " Generators" . TextFormat::GOLD . " * " . TextFormat::WHITE . $stack . TextFormat::RESET . TextFormat::GOLD . " pumping ores.");
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param BlockBreakEvent $event
     *
     * @throws LevelException
     * @throws TranslationException
     * @throws UtilsException
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        $item = $event->getItem();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($player->isCreative()) {
            return;
        }
        if(!ItemManager::canUseTool($player, $item)) {
            $level = ItemManager::getLevelToUseTool($item);
            $player->playErrorSound();
            $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "You must be level $level");
            $event->cancel();
            return;
        }
        $block = $event->getBlock();
        $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
        if(!$block instanceof Ore && $block->getId() !== BlockLegacyIds::END_STONE) {
            if(!$block instanceof Meteor) {
                if(!$tile instanceof Meteorite) {
                    if(!$tile instanceof CrudeOre) {
                        if(!$tile instanceof OreGenerator) {
                            $worlds = [$this->core->getServer()->getWorldManager()->getDefaultWorld()->getFolderName(), "executive", "boss", "koth", "lounge"];
                            if(in_array($block->getPosition()->getWorld()->getFolderName(), $worlds)) {
                                if($player->hasPermission(DefaultPermissions::ROOT_OPERATOR) and $player->isSneaking()) {
                                    return;
                                }
                                $event->cancel();
                                return;
                            }
                        }
                        else {
                            if($player instanceof NexusPlayer and $player->isSneaking()) {
                                return;
                            }
                            $event->cancel();
                            return;
                        }
                    }
                    else {
                        if($player instanceof NexusPlayer and $player->isSneaking()) {
                            return;
                        }
                    }
                }
            }
        }
        $levelNeeded = 0;
        $type = $block;
        if($tile instanceof CrudeOre) {
            $type = BlockFactory::getInstance()->get($tile->getOre(), 0);
            $levelNeeded = ItemManager::getLevelToMineOre($type);
        }
        if($levelNeeded > $player->getDataSession()->getTotalXPLevel()) {
            $player->playErrorSound();
            $player->sendTitleTo(TextFormat::RED . TextFormat::BOLD . "NOTICE", TextFormat::GRAY . "You must be level $levelNeeded");
            $event->cancel();
            return;
        }
        if(!$type->getBreakInfo()->isToolCompatible($player->getInventory()->getItemInHand())) {
            $player->playErrorSound();
            $player->sendTitleTo(TextFormat::RED . TextFormat::BOLD . "NOTICE", TextFormat::GRAY . "This tool isn't strong enough");
            $event->cancel();
            return;
        }
        if($player->getWorld()->getFolderName() === "executive") {
            if($block->getId() === BlockLegacyIds::END_STONE) {
                $event->setDrops([$this->createBuildingBlock()]);
                ExecutiveMineHeartbeatTask::removeFloatingText($block->getPosition());
            }
            WarpMenuInventory::updateExecutiveSession($player);
        }
        $drops = $event->getDrops();
        $event->setXpDropAmount(0);
        if($tile instanceof CrudeOre) {
            if($tile->getAmount() >= 1) {
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new CrudeOreRegenerateTask($block->getPosition(), $tile->getOre(), $tile->getAmount() - 1, $tile->getRarity(), $tile->isRefined()), 1);
                $ore = BlockFactory::getInstance()->get($tile->getOre(), 0);
                $drops = [];
                $blockDrops = $ore->getDrops($item);
                if($tile->isRefined()) {
                    $blockDrops = [ItemManager::getRefinedDrop($block)];
                }
                foreach($blockDrops as $drop) {
                    $drops[] = $drop->setCount($drop->getCount() * mt_rand(12, 24));
                }
            }
            else {
                $event->cancel();
                return;
            }
        }
        if($tile instanceof Meteorite) {
            $stack = $tile->getStack();
            --$stack;
            if($stack > 0) {
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new MeteoriteRegenerateTask($block->getPosition(), $stack, $tile->isRefined()), 1);
                $level = $player->getDataSession()->getTotalXPLevel();
                $block = ItemManager::getOreByLevel($level);
                $blockDrops = $block->getDrops($item);
                if($tile->isRefined()) {
                    $blockDrops = [ItemManager::getRefinedDrop($block)];
                }
                $drops = [];
                foreach($blockDrops as $drop) {
                    $drops[] = $drop->setCount($drop->getCount() * mt_rand(16, 32));
                }
            }
            else {
                $tile->getPosition()->getWorld()->removeTile($tile);
                $drops = [];
            }
        }
        if($tile instanceof RushOreBlock && $block instanceof \core\level\block\RushOreBlock) {
            $hits = $tile->getHits();
            --$hits;
            if($hits > 0) {
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new RushOreRegenerateTask($block->getPosition(), $hits, $block), 1);
            } else {
                $floatingID = serialize($block->getPosition()->asVector3());
                foreach (Nexus::getInstance()->getServer()->getOnlinePlayers() as $ent) {
                    if($ent instanceof NexusPlayer) {
                        $ent->removeFloatingText($floatingID);
                    }
                }
                if(isset(SpawnRushOreTask::$fancyFloaties[$floatingID])) {
                    unset(SpawnRushOreTask::$fancyFloaties[$floatingID]);
                }
                $tile->getPosition()->getWorld()->removeTile($tile);
                $msg = $tile->getColor($block->getId()) . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::UNDERLINE . $tile->getColor($block->getId()) . $player->getName() . TextFormat::RESET . TextFormat::GRAY . " has won the " . TextFormat::RESET . $tile->getColor($block->getId()) . $tile->getBaseInfo($block->getId()) . TextFormat::RESET . TextFormat::GRAY . " at {$block->getPosition()->getX()}, {$block->getPosition()->getY()}, {$block->getPosition()->getZ()}";
                Nexus::getInstance()->getServer()->broadcastMessage($msg);
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($block) : void {
                    $block->getPosition()->getWorld()->setBlock($block->getPosition(), $block->getNormalOreBlock());
                }), 1);
            }
        }
        foreach($drops as $drop) {
            if(isset(TransfuseEnchantment::ITEMS[$drop->getId()])) {
                $random = mt_rand(1, 80);
                $level = $item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::TRANSFUSE));
                $level *= $player->getCESession()->getItemLuckModifier();
                if($level >= $random) {
                    $drop = ItemFactory::getInstance()->get(TransfuseEnchantment::ITEMS[$drop->getId()], 0, $drop->getCount());
                }
            }
            if($block instanceof Ore or $tile instanceof Meteorite or $tile instanceof CrudeOre) {
                if($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::ORE_MAGNET)) && ($item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::ORE_MAGNET)) < 6 && !SetUtils::isWearingFullSet($player, "santa"))) {
                    $count = mt_rand(1, $item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::ORE_MAGNET))) * mt_rand(1, 2);
                    if(PlotManager::isPlotWorld($player->getWorld())) {
                        $count *= 0.75;
                        $count = (int)floor($count);
                    }
                    $drop->setCount($drop->getCount() + $count);
                } else {
                    if(SetUtils::isWearingFullSet($player, "santa")) {
                        $count = mt_rand(1, 6);
                        if(PlotManager::isPlotWorld($player->getWorld())) {
                            $count *= 0.75;
                            $count = (int)ceil($count);
                        }
                        $drop->setCount($drop->getCount() + $count);
                    }
                }
                if($item instanceof Pickaxe) {
                    $hoarder = 1 + ($item->getAttribute(Pickaxe::HOARDER) / 100);
                    $drop->setCount((int)ceil($drop->getCount() * $hoarder));
                    if($tile instanceof Meteorite) {
                        $mm = 1 + ($item->getAttribute(Pickaxe::METEORITE_MASTERY) / 100);
                        $drop->setCount((int)ceil($drop->getCount() * $mm));
                    }
                }
            }
            if(!$player->getInventory()->canAddItem($drop)) {
                $player->playErrorSound();
                $player->sendTitleTo(TextFormat::DARK_RED . TextFormat::BOLD . "FULL INVENTORY", TextFormat::GRAY . "Clear out your inventory!");
                $event->setDrops([]);
                break;
            }
            $compress = false;
            if($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::ALCHEMY))) {
                $random = mt_rand(1, 40);
                $level = $item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::ALCHEMY));
                $level *= $player->getCESession()->getItemLuckModifier();
                if($level >= $random and ($block instanceof Ore or $tile instanceof Meteorite or $tile instanceof CrudeOre)) {
                    $compress = true;
                }
            }
            $player->addItem($drop, $compress);
        }
        if($block instanceof Ore or $tile instanceof Meteorite or $tile instanceof CrudeOre) {
            if(!$player->getCESession()->hasExplode()) {
                $xp = 0;
                if($block instanceof Ore) {
                    $xp = $block->getXPDrop();
                }
                if($tile instanceof CrudeOre) {
                    /** @var Ore $ore */
                    $ore = BlockFactory::getInstance()->get($tile->getOre(), 0);
                    $xp = $ore->getXPDrop();
                    $xp = (int)floor($xp * 0.66);
                }
                if($tile instanceof Meteorite) {
                    $ore = ItemManager::getOreByLevel($player->getDataSession()->getTotalXPLevel());
                    $xp = $ore->getXPDrop();
                }
                $xp = (int)floor($xp * (1 - ($player->getGuardTax() / 100)));
                $player->getDataSession()->addToXP($xp);
                if($item instanceof Pickaxe) {
                    $energy = 0;
                    if($block instanceof Ore) {
                        $energy = $block->getEnergyDrop();
                    }
                    else {
                        if(isset($ore)) {
                            $energy = $ore->getEnergyDrop();
                        }
                    }
                    if($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::ENERGY_COLLECTOR))) {
                        $energy *= (1 + ($item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::ENERGY_COLLECTOR)) * 0.45));
                        if($item->getNamedTag()->getString(SetManager::SET, "") === "demolition" && SetUtils::isWearingFullSet($player, "demolition")) $energy *= 1.1;
                    }
                    if($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::ENERGY_HOARDER))) {
                        $energy *= (4 + ($item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::ENERGY_COLLECTOR)) * 0.35));
                        if($item->getNamedTag()->getString(SetManager::SET, "") === "demolition" && SetUtils::isWearingFullSet($player, "demolition")) $energy *= 1.1;
                    }
                    $rank = $player->getDataSession()->getRank();
                    if($rank->getBooster() > 0) {
                        $boost = 1 + $rank->getBooster();
                        $energy *= $boost;
                    }
                    $ev = new EarnEnergyEvent($player, (int)$energy);
                    $ev->call();
                    $item->addEnergy($ev->getAmount(), $player);
                    if(abs($block->getBreakInfo()->getToolHarvestLevel() - $item->getBlockToolHarvestLevel()) <= 1) {
                        $item->addBlock();
                        $gang = $player->getDataSession()->getGang();
                        if($gang !== null) {
                            $gang->addValue(1);
                        }
                    }
                    if($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::WARP_MINER)) or $item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::TIME_WARP))) {
                        $item->addWarpMinerMined($player);
                    }
                }
                $player->getDataSession()->addBlocksMined();
            }
        }
        $player->getInventory()->setItemInHand($item);
        $event->setDrops([]);
    }

    /**
     * @priority LOWEST
     * @param BlockPlaceEvent $event
     */
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$block instanceof Ore) {
            if($player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                return;
            }
            $worldName = $block->getPosition()->getWorld()->getFolderName();
            if($worldName === $this->core->getServer()->getWorldManager()->getDefaultWorld()->getFolderName()){
                $event->cancel();
                return;
            }
            if ($worldName === "executive" && $block->getId() !== BlockLegacyIds::END_STONE){
                $event->cancel();
                return;
            }
        }
        else {
            if($player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                return;
            }
            $event->cancel();
        }
    }

    /**
     * @priority HIGHEST
     * @param BlockPlaceEvent $event
     */
    public function onBuildingBlockPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $position = $block->getPosition();
        $world = $player->getWorld();
        if($world->getFolderName() === "executive" && $block->getId() === BlockLegacyIds::END_STONE) {
            $event->uncancel();
            $this->core->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($world, $position) : void {
                $world->setBlock($position, VanillaBlocks::AIR());
            }), 40);
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageByEntityEvent
     */
    public function onAttack(EntityDamageByEntityEvent $event) {
        if($event->getDamager()->getWorld()->getFolderName() === "executive") {
            if($event->getEntity() instanceof NexusPlayer) {
                $event->cancel();
            }
        }
    }

    private function createBuildingBlock() : Item {
        $block = VanillaBlocks::END_STONE()->asItem()->setCount(1);
        $block->setCustomName(TextFormat::YELLOW . TextFormat::BOLD . "Building Block");
        $block->setLore([
            TextFormat::RESET . TextFormat::GRAY . "Can be placed in the Executive Mine",
            TextFormat::RESET . TextFormat::GRAY . "to allow you to reach restricted areas!",
            "",
            TextFormat::RESET . TextFormat::GRAY . "Disappears after " . TextFormat::UNDERLINE . "2 seconds"
        ]);
        return $block;
    }

    public function onTeleport(EntityTeleportEvent $event) {
        $player = $event->getEntity();
        if($player instanceof NexusPlayer) {
            $player->getInventory()->remove(VanillaBlocks::END_STONE()->asItem());
        }
    }

    /**
     * @param EntitySpawnEvent $event
     */
    public function onEntitySpawn(EntitySpawnEvent $event): void {
        $entity = $event->getEntity();
        if($entity instanceof ExperienceOrb) {
            $entity->flagForDespawn();
        }
    }

    public function onMove(PlayerMoveEvent $event) {
        $entPos = $event->getPlayer()->getPosition();
        if($event->getPlayer()->getWorld()->getFolderName() === "boss" && $entPos->getY() >= 30 && BossSummonTask::getBossFight() !== null && BossSummonTask::getBossFight()->inArena($event->getPlayer())) {
            $boss = BossSummonTask::getBossFight()->getBoss();
            $pos = $boss->getPosition();
            $event->getPlayer()->knockBack($entPos->x - $pos->x, $entPos->z - $pos->z, -3);
            if($boss->tauntTicks === 0 && $boss->getTarget() !== null && $boss->getTarget()->getName() === $event->getPlayer()->getName()) {
                $boss->tauntTicks = 94;
                $boss->playSound("mob.nexus_hades.say");
                $boss->playAnimation("animation.nexus_hades.taunt");
            }
        }
    }

    /**
     * @param PlayerDropItemEvent $event
     * @return void
     */
    public function onDropItem(PlayerDropItemEvent $event) : void { // TODO: Test && come up with a permanent solution
        if($event->getItem()->getId() === VanillaBlocks::SUNFLOWER()->asItem()->getId()) {
            $event->cancel();
            $event->getPlayer()->sendMessage(TextFormat::RED . TextFormat::BOLD . "(!)" . TextFormat::RESET . TextFormat::RED . " You cannot drop prestige tokens! Please use /trade to give them to another player, or /trash to dispose them.");
        }
    }

    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $world = $player->getWorld();
        if($world->getFolderName() === "executive" && !isset(WarpMenuInventory::$executiveSessions[$player->getXuid()])) {
            $player->sendMessage(TextFormat::RED . "You are not currently in an Executive Mine session!");
            $event->cancel();
        }
    }

    public function onDamage(EntityDamageByEntityEvent $event) {
        $player = $event->getDamager();
        $world = $player->getWorld();
        if($player instanceof NexusPlayer && $event->getEntity() instanceof ExecutiveEnderman) {
            if ($world->getFolderName() === "executive" && !isset(WarpMenuInventory::$executiveSessions[$player->getXuid()])) {
                $player->sendMessage(TextFormat::RED . "You are not currently in an Executive Mine session!");
                $event->cancel();
            }
        }
    }
}