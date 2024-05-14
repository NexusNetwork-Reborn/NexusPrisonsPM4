<?php

declare(strict_types = 1);

namespace core\game\economy\event;

use core\player\NexusPlayer;
use pocketmine\event\player\PlayerEvent;
use pocketmine\item\Item;

class ItemSellEvent extends PlayerEvent {

    /** @var Item */
    private $item;

    /** @var float */
    private $profit;

    /**
     * ItemSellEvent constructor.
     *
     * @param NexusPlayer $player
     * @param Item $item
     * @param float $profit
     */
    public function __construct(NexusPlayer $player, Item $item, float $profit) {
        $this->player = $player;
        $this->item = $item;
        $this->profit = $profit;
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
    public function getProfit(): float {
        return $this->profit;
    }
}