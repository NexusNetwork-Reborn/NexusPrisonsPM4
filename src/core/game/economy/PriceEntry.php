<?php

declare(strict_types = 1);

namespace core\game\economy;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class PriceEntry {

    /** @var float|null */
    private $sellPrice;

    /** @var float|null */
    private $buyPrice;

    /** @var Item */
    private $item;

    /** @var string */
    private $name;

    /** @var int|null */
    private $place;

    /** @var int */
    private $level;

    /**
     * PriceEntry constructor.
     *
     * @param Item $item
     * @param int|null $place
     * @param string|null $name
     * @param float|null $sellPrice
     * @param float|null $buyPrice
     * @param int $level
     */
    public function __construct(Item $item, ?int $place = null, ?string $name = null, ?float $sellPrice = null, ?float $buyPrice = null, int $level = 0) {
        $this->item = $item;
        $this->name = $name;
        if($name === null) {
            $this->name = $this->item->getName();
        }
        $this->sellPrice = $sellPrice;
        $this->buyPrice = $buyPrice;
        $this->level = $level;
        $this->place = $place;
    }

    /**
     * @return int|null
     */
    public function getPlace(): ?int {
        return $this->place;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return float|null
     */
    public function getSellPrice(): ?float {
        return $this->sellPrice;
    }

    /**
     * @return float|null
     */
    public function getBuyPrice(): ?float {
        return $this->buyPrice;
    }

    /**
     * @return int
     */
    public function getLevel(): int {
        return $this->level;
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public function equal(Item $item): bool {
        if($item->getId() === VanillaItems::EMERALD()->getId() && $this->item->getId() === VanillaItems::EMERALD()->getId()) {
            return $this->item->equals($item, false, true) && $item->getName() === VanillaItems::EMERALD()->getName();
        }
        return $this->item->equals($item, false, true);
    }
}