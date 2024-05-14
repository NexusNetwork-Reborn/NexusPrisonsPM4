<?php

declare(strict_types = 1);

namespace core\game\boop\handler;

use core\Nexus;
use core\player\NexusPlayer;
use core\translation\TranslationException;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;

class HandlerListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * HandlerListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority HIGHEST
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event): void {
        if($this->core->getGameManager()->getBOOPManager()->getHandlerManager()->isHalted()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $handlerManager = $this->core->getGameManager()->getBOOPManager()->getHandlerManager();
        $handlerManager->getFlyHandler()->check($player, $event);
//        $handlerManager->getJetpackHandler()->check($player, $event);
//        $handlerManager->getSpeedHandler()->check($player, $event);
//        if($player->getPing() > 200) {
//            return;
//        }
//        $handlerManager->getNoClipHandler()->check($player, $event);
    }

    /**
     * @priority HIGH
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        if($this->core->getGameManager()->getBOOPManager()->getHandlerManager()->isHalted()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $handlerManager = $this->core->getGameManager()->getBOOPManager()->getHandlerManager();
        $handlerManager->getNukeHandler()->check($player, $event);
        if($player->getEffects()->has(VanillaEffects::HASTE())) {
            return;
        }
        $ping = $player->getNetworkSession()->getPing();
        if($ping !== null and $ping > 200) {
            return;
        }
        $handlerManager->getInstantBreakHandler()->check($player, $event);
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageEvent $event
     *
     * @throws TranslationException
     */
//    public function onEntityDamage(EntityDamageEvent $event): void {
//        if($this->core->getGameManager()->getBOOPManager()->getHandlerManager()->isHalted()) {
//            return;
//        }
//        $handlerManager = $this->core->getGameManager()->getBOOPManager()->getHandlerManager();
//        if($event instanceof EntityDamageByEntityEvent) {
//            $damager = $event->getDamager();
//            if(!$damager instanceof NexusPlayer) {
//                return;
//            }
//            if($damager->getPing() > 200) {
//                return;
//            }
//            $handlerManager->getReachHandler()->check($damager, $event);
//        }
//    }

    /**
     * @priority NORMAL
     * @param DataPacketReceiveEvent $event
     */
//    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
//        if($this->core->getGameManager()->getBOOPManager()->getHandlerManager()->isHalted()) {
//            return;
//        }
//        $player = $event->getPlayer();
//        if(!$player instanceof NexusPlayer) {
//            return;
//        }
//        $handlerManager = $this->core->getGameManager()->getBOOPManager()->getHandlerManager();
//        $handlerManager->getAutoClickerHandler()->check($player, $event);
//        $handlerManager->getSpeedHandler()->check($player, $event);
//    }
}