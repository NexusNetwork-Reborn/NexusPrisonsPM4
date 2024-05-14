<?php

declare(strict_types = 1);

namespace core\game\economy\event;

use core\player\NexusPlayer;
use pocketmine\event\player\PlayerEvent;
use pocketmine\item\Item;

class ItemBuyEvent extends PlayerEvent {

    /** @var Item */
    private $item;

    /** @var float */
    private $spent;

    /**
     * ItemBuyEvent constructor.
     *
     * @param NexusPlayer $player
     * @param Item $item
     * @param float $spent
     */
    public function __construct(NexusPlayer $player, Item $item, float $spent) {
        $this->player = $player;
        $this->item = $item;
        $this->spent = $spent;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    /**
     * @return float
     */
    public function getSpent(): float {
        return $this->spent;
    }
}