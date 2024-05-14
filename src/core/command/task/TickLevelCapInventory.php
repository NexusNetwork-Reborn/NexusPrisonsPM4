<?php

namespace core\command\task;

use core\command\inventory\LevelCapInventory;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;

class TickLevelCapInventory extends Task {

    /** @var LevelCapInventory */
    private $inventory;

    /**
     * TickLevelCapInventory constructor.
     *
     * @param LevelCapInventory $inventory
     */
    public function __construct(LevelCapInventory $inventory) {
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