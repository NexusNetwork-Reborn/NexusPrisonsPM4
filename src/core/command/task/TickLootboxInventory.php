<?php

namespace core\command\task;

use core\command\inventory\LootboxInventory;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;

class TickLootboxInventory extends Task {

    /** @var LootboxInventory */
    private $inventory;

    /**
     * TickLootboxInventory constructor.
     *
     * @param LootboxInventory $inventory
     */
    public function __construct(LootboxInventory $inventory) {
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