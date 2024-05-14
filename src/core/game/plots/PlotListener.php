<?php

namespace core\game\plots;

use core\game\plots\plot\PermissionManager;
use core\level\tile\CrudeOre;
use core\level\tile\OreGenerator;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Door;
use pocketmine\block\tile\Container;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\item\ItemBlock;
use pocketmine\math\Facing;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class PlotListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * PlotListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     * @param PlayerInteractEvent $event
     */
//    public function onPlayerInteract(PlayerInteractEvent $event): void {
//        $action = $event->getAction();
//        $plotManager = $this->core->getGameManager()->getPlotManager();
//        $player = $event->getPlayer();
//        if(!$player instanceof NexusPlayer) {
//            return;
//        }
//        $createSession = $plotManager->getPlotCreateSession($player);
//        if($createSession !== null) {
//            if($event->getBlock()->getId() !== BlockLegacyIds::WOOL) {
//                $player->sendMessage("Can only interact wool");
//                return;
//            }
//            if($action === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
//                $createSession->setFirstPosition($event->getBlock()->getPosition());
//                $player->sendMessage("First position set");
//            }
//            if($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
//                if($player->isSneaking()) {
//                    $createSession->setSpawn($event->getBlock()->getPosition());
//                    $player->sendMessage("Spawn position set");
//                }
//                else {
//                    $createSession->setSecondPosition($event->getBlock()->getPosition());
//                    $player->sendMessage("Second position set");
//                }
//            }
//            $createSession->checkConfirmation($player);
//        }
//    }

    /**
     * @priority LOW
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $world = $player->getWorld();
        if(PlotManager::isPlotWorld($world)) {
            $plotManager = $this->core->getGameManager()->getPlotManager();
            $pos = $block->getPosition();
            if($player->getInventory()->getItemInHand()->getBlock()->getId() !== BlockLegacyIds::AIR) {
                $pos = $pos->getSide($event->getFace());
            }
            $plot = $plotManager->getPlotInPosition($pos);
            if($plot !== null) {
                if($player->hasPermission(DefaultPermissions::ROOT_OPERATOR) && $player->isCreative(true)) {
                    return; // No cancel
                }
                $owner = $plot->getOwner();
                if($owner !== null) {
                    if($owner->getUsername() === $player->getName()) {
                        return;
                    }
                    $tile = $player->getWorld()->getTile($block->getPosition());
                    $user = $owner->getUser($player->getName());
                    if(($user !== null) and ($tile instanceof Container) and $user->getPermissionManager()->hasPermission(PermissionManager::PERMISSION_CHESTS)) {
                        return;
                    }
                    if(($user !== null) and ($block instanceof Door) and $user->getPermissionManager()->hasPermission(PermissionManager::PERMISSION_DOORS)) {
                        return;
                    }
                    if($user === null) {
                        $event->cancel();
                        $player->sendMessage(Translation::getMessage("noPermission"));
                        return;
                    }
                    else {
                        return;
                    }
                }
                $event->cancel();
                $player->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            if(!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                $event->cancel();
                return;
            }
            if(!$player->isSneaking()) {
                $event->cancel();
                return;
            }
        }
    }

    /**
     * @priority LOW
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $plotManager = $this->core->getGameManager()->getPlotManager();
        $world = $player->getWorld();
        if(PlotManager::isPlotWorld($world)) {
            $plot = $plotManager->getPlotInPosition($block->getPosition());
            if($player->hasPermission(DefaultPermissions::ROOT_OPERATOR) && $player->isCreative(true)) {
                return; // No cancel
            }
            if($plot !== null) {
                $owner = $plot->getOwner();
                if($owner !== null) {
                    if($owner->getUsername() === $player->getName()) {
                        return;
                    }
                    $user = $owner->getUser($player->getName());
                    if($user !== null and $user->getPermissionManager()->hasPermission(PermissionManager::PERMISSION_BREAK)) {
                        return;
                    }
                    $tile = $player->getWorld()->getTile($block->getPosition());
                    if($user !== null and $tile instanceof CrudeOre and (!$player->isSneaking()) and $user->getPermissionManager()->hasPermission(PermissionManager::PERMISSION_MINE)) {
                        return;
                    }
                    $event->cancel();
                    $player->sendMessage(Translation::getMessage("noPermission"));
                    return;
                }
                $event->cancel();
                $player->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            $event->cancel();
            return;
//            if(!$player->isSneaking()) {
//                $event->cancel();
//                return;
//            }
        }
    }

    /**
     * @priority LOW
     * @param BlockPlaceEvent $event
     */
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlockReplaced();
        $plotManager = $this->core->getGameManager()->getPlotManager();
        $world = $player->getWorld();
        if(PlotManager::isPlotWorld($world)) {
            $plot = $plotManager->getPlotInPosition($block->getPosition());
            if($player->hasPermission(DefaultPermissions::ROOT_OPERATOR) && $player->isCreative(true)) {
                return; // No cancel
            }
            if($plot !== null) {
                // TODO : What if null
                $count = 0;
                $crudeCount = 0;
                foreach ($plot->getTiles() as $tile) {
                    if ($tile instanceof OreGenerator) {
                        $count++;
                    } elseif($tile instanceof CrudeOre) {
                        $crudeCount++;
                    }
                }
                $max = match ($plot->getWorld()->getFolderName()) {
                    "citizen" => 4,
                    "merchant" => 6,
                    "king" => 8,
                    default => 2,
                };
                $maxCrude = $max * 4;

                if (\core\game\item\types\custom\OreGenerator::isInstanceOf($event->getItem()) && $count >= $max) {
                    $event->cancel();
                    $player->sendMessage(TextFormat::RED . TextFormat::BOLD . "(!)" . TextFormat::RESET . TextFormat::RED . " You can only place a maximum of $max generators in this plot!");
                    return;
                } elseif(\core\game\item\types\custom\CrudeOre::isInstanceOf($event->getItem()) && $crudeCount >= $maxCrude) {
                    $event->cancel();
                    $player->sendMessage(TextFormat::RED . TextFormat::BOLD . "(!)" . TextFormat::RESET . TextFormat::RED . " You can only place a maximum of $max crude ores in this plot!");
                    return;
                }
            }

            if($plot !== null) {
                $owner = $plot->getOwner();
                if($owner !== null) {
                    if($owner->getUsername() === $player->getName()) {
                        return;
                    }
                    $user = $owner->getUser($player->getName());
                    if($user !== null and $user->getPermissionManager()->hasPermission(PermissionManager::PERMISSION_PLACE)) {
                        return;
                    }
                    $event->cancel();
                    $player->sendMessage(Translation::getMessage("noPermission"));
                    return;
                }
                $event->cancel();
                $player->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            if(!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                $event->cancel();
                return;
            }
            if(!$player->isSneaking()) {
                $event->cancel();
                return;
            }
            $event->cancel();
            return;
        }
    }

    /**
     * @priority LOW
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        $player = $event->getEntity();
        $world = $player->getWorld();
        if(PlotManager::isPlotWorld($world)) {
            $event->cancel();
        }
    }
//    /**
//     * @param WorldLoadEvent $event
//     */
//    public function onWorldLoad(WorldLoadEvent $event): void {
//        $world = $event->getWorld();
//        switch(($world->getFolderName())) {
//            case "citizen":
//            case "merchant":
//            case "king":
//                foreach($world->getChunks() as $chunk) {
//                    foreach($chunk->getTiles() as $tile) {
//                        $world->setBlock($tile->getPosition(), VanillaBlocks::STONE());
//                        $world->removeTile($tile);
//                    }
//                }
//                break;
//            default:
//                break;
//        }
//    }
}