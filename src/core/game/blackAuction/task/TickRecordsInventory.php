<?php

namespace core\game\blackAuction\task;

use core\game\auction\inventory\AuctionListInventory;
use core\game\blackAuction\inventory\BlackAuctionMainInventory;
use core\game\blackAuction\inventory\BlackAuctionRecordsInventory;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;

class TickRecordsInventory extends Task {

    /** @var BlackAuctionRecordsInventory */
    private $inventory;

    /**
     * TickMainInventory constructor.
     *
     * @param BlackAuctionMainInventory $inventory
     */
    public function __construct(BlackAuctionRecordsInventory $inventory) {
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