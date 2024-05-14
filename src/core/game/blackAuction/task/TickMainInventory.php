<?php

namespace core\game\blackAuction\task;

use core\game\auction\inventory\AuctionListInventory;
use core\game\blackAuction\inventory\BlackAuctionMainInventory;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;

class TickMainInventory extends Task {

    /** @var BlackAuctionMainInventory */
    private $inventory;

    /**
     * TickMainInventory constructor.
     *
     * @param BlackAuctionMainInventory $inventory
     */
    public function __construct(BlackAuctionMainInventory $inventory) {
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