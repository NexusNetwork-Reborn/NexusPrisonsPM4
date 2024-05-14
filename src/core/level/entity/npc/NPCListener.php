<?php
declare(strict_types=1);

namespace core\level\entity\npc;

use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

class NPCListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * NPCListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     *
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        foreach($this->core->getLevelManager()->getNPCs() as $npc) {
            $npc->spawnTo($player);
        }
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
        foreach($this->core->getLevelManager()->getNPCs() as $npc) {
            if($npc->getPosition()->getWorld()->getFolderName() === $event->getPlayer()->getWorld()->getFolderName()) {
                if($npc->isSpawned($player)) {
                    if($npc->getPosition()->distance($event->getTo()) <= 8) {
                        $npc->move($player);
                        continue;
                    }
                }
                else {
                    $npc->spawnTo($player);
                }
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
        foreach($this->core->getLevelManager()->getNPCs() as $npc) {
            if($npc->isSpawned($player)) {
                $npc->despawnFrom($player);
            }
        }
    }

    /**
     * @param EntityTeleportEvent $event
     */
    public function onEntityTeleport(EntityTeleportEvent $event) {
        $entity = $event->getEntity();
        if($entity instanceof NexusPlayer) {
            foreach($this->core->getLevelManager()->getNPCs() as $npc) {
                if($npc->isSpawned($entity) and $npc->getPosition()->getWorld()->getFolderName() !== $entity->getWorld()->getFolderName()) {
                    $npc->despawnFrom($entity);
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
            $npc = $this->core->getLevelManager()->getNPC($pk->trData->getActorRuntimeId());
            if($npc === null) {
                return;
            }
            $callable = $npc->getCallable();
            $callable($player);
        }
    }
}