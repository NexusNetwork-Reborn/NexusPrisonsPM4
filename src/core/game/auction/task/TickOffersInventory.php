<?php

namespace core\game\auction\task;

use core\game\auction\inventory\AuctionOffersInventory;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;

class TickOffersInventory extends Task {

    /** @var AuctionOffersInventory */
    private $inventory;

    /**
     * TickOffersInventory constructor.
     *
     * @param AuctionOffersInventory $inventory
     */
    public function __construct(AuctionOffersInventory $inventory) {
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