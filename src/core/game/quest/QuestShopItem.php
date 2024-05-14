<?php

declare(strict_types = 1);

namespace core\game\quest;

use pocketmine\item\Item;

class QuestShopItem {

    /** @var Item */
    protected $item;

    /** @var int */
    protected $price;

    /**
     * QuestShopItem constructor.
     *
     * @param Item $item
     * @param int $price
     */
    public function __construct(Item $item, int $price) {
        $this->item = $item;
        $this->price = $price;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    /**
     * @return int
     */
    public function getPrice(): int {
        return $this->price;
    }
}