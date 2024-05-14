<?php

namespace core\game\plots\task;

use core\game\plots\command\inventory\PlotListSectionInventory;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;

class TickPlotsInventory extends Task {

    /** @var PlotListSectionInventory */
    private $inventory;

    /**
     * TickPlotsInventory constructor.
     *
     * @param PlotListSectionInventory $inventory
     */
    public function __construct(PlotListSectionInventory $inventory) {
        $this->inventory = $inventory;
        $this->inventory->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {
            $this->cancel();
        });
    }

    public function onRun(): void {
        if(!$this->inventory->tick()) {
            $this->cancel();
        }
    }
}