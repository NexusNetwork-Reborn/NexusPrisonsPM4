<?php

namespace core\game\boop\handler\types\hacks;

use core\Nexus;
use core\player\NexusPlayer;
use core\game\boop\handler\Handler;
use pocketmine\event\Event;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

class AutoClickerHandler extends Handler {

    /** @var int */
    private $autoClickTime;

    /**
     * AutoClickerHandler constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        parent::__construct($core);
        $this->autoClickTime = time();
    }

    /**
     * @param NexusPlayer $player
     * @param Event $event
     */
    public function check(NexusPlayer $player, Event $event): void {
        if($event instanceof DataPacketReceiveEvent) {
            $packet = $event->getPacket();
            if($packet instanceof InventoryTransactionPacket) {
                if($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
                    ++$player->cps;
                    if($this->autoClickTime < time()) {
                        $multiplier = time() - $this->autoClickTime;
                        $cps = floor($player->cps / $multiplier);
                        if($cps >= 30) {
                            $reason = "Auto-clicking. CPS: $cps";
                            if($this->handleViolations($player, $reason)) {
                                $event->cancel();
                            }
                            $player->cps = 0;
                            return;
                        }
                        $this->autoClickTime = time();
                    }
                }
            }
        }
    }
}