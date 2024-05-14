<?php

namespace core\game\item\types;

use core\player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;

abstract class Interactive extends CustomItem {

    /**
     * @param NexusPlayer $player
     * @param Item $itemClickedWith
     * @param Item $itemClicked
     * @param SlotChangeAction $itemClickedWithAction
     * @param SlotChangeAction $itemClickedAction
     *
     * @return bool
     */
    public function onCombine(NexusPlayer $player, Item $itemClickedWith, Item $itemClicked, SlotChangeAction $itemClickedWithAction, SlotChangeAction $itemClickedAction): bool {
        return true;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param int $face
     * @param Block $block
     */
    public function onInteract(NexusPlayer $player, Inventory $inventory, Item $item, int $face, Block $block): void {

    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {

    }
}