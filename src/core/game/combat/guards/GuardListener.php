<?php
declare(strict_types=1);

namespace core\game\combat\guards;

use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\utils\TextFormat;

class GuardListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * GuardListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     *
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $world = $player->getWorld();
        if($world === null) {
            return;
        }
        $bb = $player->getBoundingBox()->expandedCopy(24, 24, 24);
        foreach($this->core->getGameManager()->getCombatManager()->getNearbyGuards($bb, $world) as $guard) {
            if($guard->isSpawned($player)) {
                if($guard->getStation()->distance($player->getPosition()) <= 8) {
                    $guard->move($player);
                }
            }
            else {
                $guard->spawnTo($player);
            }
        }
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        foreach($this->core->getGameManager()->getCombatManager()->getGuards() as $guard) {
            if($guard->isSpawned($player)) {
                $guard->despawnFrom($player);
            }
        }
    }

    /**
     * @param EntityTeleportEvent $event
     */
    public function onEntityTeleport(EntityTeleportEvent $event) {
        $entity = $event->getEntity();
        if($entity instanceof NexusPlayer) {
            foreach($this->core->getGameManager()->getCombatManager()->getGuards() as $guard) {
                if($guard->isSpawned($entity) and $guard->getStation()->getWorld()->getFolderName() !== $entity->getWorld()->getFolderName()) {
                    $guard->despawnFrom($entity);
                }
            }
        }
    }

    /**
     * @priority NORMAL
     *
     * @param DataPacketReceiveEvent $event
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
        $pk = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($pk instanceof InventoryTransactionPacket and $pk->trData instanceof UseItemOnEntityTransactionData) {
            $guard = $this->core->getGameManager()->getCombatManager()->getGuard($pk->trData->getActorRuntimeId());
            if($guard === null) {
                return;
            }
            $player->playErrorSound();
            $player->sendMessage(TextFormat::DARK_GRAY . "[" . $guard->getNameTag() . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::RED . "I don't think you want to do that!");
        }
    }
}