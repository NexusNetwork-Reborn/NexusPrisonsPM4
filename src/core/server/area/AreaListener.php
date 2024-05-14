<?php
declare(strict_types=1);

namespace core\server\area;

use core\game\item\types\Interactive;
use core\game\wormhole\entity\ExecutiveEnderman;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\TranslationException;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\DoubleTallGrass;
use pocketmine\block\Flower;
use pocketmine\block\Grass;
use pocketmine\block\TallGrass;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\Bucket;
use pocketmine\permission\DefaultPermissions;
use pocketmine\world\Position;

class AreaListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * AreaListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority HIGH
     *
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $to = $event->getTo();
        if($to->getY() < 0) {
            if($to->getWorld()->getFolderName() === $this->core->getServer()->getWorldManager()->getDefaultWorld()->getFolderName()) {
                return;
            }
            $player->teleport($to->getWorld()->getSpawnLocation());

            $pos = new Position($to->getX(), 0, $to->getZ(), $to->getWorld());
            $to->getWorld()->setBlock($pos, VanillaBlocks::BEDROCK());
            $player->teleport(Position::fromObject($pos->add(0, 1, 0), $pos->getWorld()));
        }
    }

    /**
     * @priority HIGH
     *
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        if((!$player instanceof NexusPlayer) or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            return;
        }
        $block = $event->getBlock();
        $areaManager = $this->core->getServerManager()->getAreaManager();
        $areas = $areaManager->getAreasInPosition($block->getPosition());
        if($areas !== null) {
            foreach($areas as $area) {
                if($area->getEditFlag() === false) {
                    if ($block->getId() !== BlockLegacyIds::ENDER_CHEST) {
                        $event->cancel();
                    }

                    return;
                }
            }
        }
    }

    /**
     * @param PlayerBucketEmptyEvent $event
     */
    public function onPlayerBucketEmpty(PlayerBucketEmptyEvent $event): void {
        $block = $event->getBlockClicked();
        if($block->getPosition()->getWorld()->getFolderName() === $this->core->getServer()->getWorldManager()->getDefaultWorld()->getFolderName()) {
            $event->cancel();
            return;
        }
    }

    public function onPlayerItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        if((!$player instanceof NexusPlayer) or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            return;
        }
        $item = $event->getItem();
        $block = $event->getPlayer();
        $areaManager = $this->core->getServerManager()->getAreaManager();
        $areas = $areaManager->getAreasInPosition($block->getPosition());
        if($areas !== null) {
            foreach($areas as $area) {
                if($area->getEditFlag() === false) {
                    $matchedItem = $this->core->getGameManager()->getItemManager()->getItem($item);
                    if($matchedItem !== null and $matchedItem instanceof Interactive) {
                        return;
                    }
                    $event->cancel();
                    return;
                }
            }
        }
    }

    /**
     * @priority LOWEST
     *
     * @param BlockBreakEvent $event
     *
     * @throws TranslationException
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        if($block->getPosition()->getY() <= 0) {
            $event->cancel();
            return;
        }
        if((!$player instanceof NexusPlayer) or ($player->hasPermission(DefaultPermissions::ROOT_OPERATOR) and $player->isSneaking())) {
            return;
        }
        if($block instanceof Flower or $block instanceof Grass or $block instanceof TallGrass or $block instanceof DoubleTallGrass) {
            $event->cancel();
            return;
        }
        $areaManager = $this->core->getServerManager()->getAreaManager();
        $areas = $areaManager->getAreasInPosition($block->getPosition());
        if($areas !== null) {
            foreach($areas as $area) {
                if($area->getEditFlag() === false) {
                    $player->sendTranslatedMessage("noPermission");
                    $event->cancel();
                    return;
                }
            }
        }
    }

    /**
     * @priority LOWEST
     *
     * @param BlockPlaceEvent $event
     *
     * @throws TranslationException
     */
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        if($player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            return;
        }
        if((!$player instanceof NexusPlayer) or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            return;
        }
        $areaManager = $this->core->getServerManager()->getAreaManager();
        $areas = $areaManager->getAreasInPosition($block->getPosition());
        if($areas !== null) {
            foreach($areas as $area) {
                if($area->getEditFlag() === false) {
                    $player->sendTranslatedMessage("noPermission");
                    $event->cancel();
                    return;
                }
            }
        }
    }

    /**
     * @priority LOWEST
     *
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        $areaManager = $this->core->getServerManager()->getAreaManager();
        $areas = $areaManager->getAreasInPosition($entity->getPosition());
        if($areas !== null) {
            foreach($areas as $area) {
                if($area->getPvpFlag() === false && !$entity instanceof ExecutiveEnderman) {
                    $event->cancel();
                    return;
                }
            }
        }
    }

    /**
     * @priority LOWEST
     *
     * @param ProjectileLaunchEvent $event
     */
    public function onProjectileLaunch(ProjectileLaunchEvent $event): void {
        $entity = $event->getEntity();
        $areaManager = $this->core->getServerManager()->getAreaManager();
        $areas = $areaManager->getAreasInPosition($entity->getPosition());
        if($areas !== null) {
            foreach($areas as $area) {
                if($area->getPvpFlag() === false) {
                    $event->cancel();
                    return;
                }
            }
        }
    }
}