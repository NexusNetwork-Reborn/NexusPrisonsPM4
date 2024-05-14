<?php

namespace core\game\auction\task;

use core\game\auction\inventory\AuctionListInventory;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;

class TickListInventory extends Task {

    /** @var AuctionListInventory */
    private $inventory;

    /**
     * TickListInventory constructor.
     *
     * @param AuctionListInventory $inventory
     */
    public function __construct(AuctionListInventory $inventory) {
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