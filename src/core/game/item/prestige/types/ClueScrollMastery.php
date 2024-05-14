<?php

namespace core\game\item\prestige\types;

use core\game\item\prestige\Prestige;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class ClueScrollMastery extends Prestige {

    /**
     * ClueScrollMastery constructor.
     */
    public function __construct() {
        parent::__construct("Clue Scroll Mastery", Pickaxe::CLUE_SCROLL_MASTERY, 10, 4);

    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "+4% - 10% chance at finding Clue Scrolls";
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return string
     */
    public function getLore(Pickaxe $pickaxe): string {
        $value = $pickaxe->getAttribute($this->getIdentifier());
        return "+$value% chance at finding Clue Scrolls";
    }

    /**
     * @return Item
     */
    public function getDefaultDisplayItem(): Item {
        return VanillaItems::PAPER();
    }
}