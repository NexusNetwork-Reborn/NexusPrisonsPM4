<?php

namespace core\game\auction\task;

use core\game\auction\inventory\AuctionConfirmationInventory;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;

class TickConfirmationInventory extends Task {

    /** @var AuctionConfirmationInventory */
    private $inventory;

    /**
     * TickConfirmationInventory constructor.
     *
     * @param AuctionConfirmationInventory $inventory
     */
    public function __construct(AuctionConfirmationInventory $inventory) {
        $this->inventory = $inventory;
        $this->inventory->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {
            $this->cancel();
        });
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        if(!$this->inventory->tick()) {
            $this->cancel();
        }
    }
}