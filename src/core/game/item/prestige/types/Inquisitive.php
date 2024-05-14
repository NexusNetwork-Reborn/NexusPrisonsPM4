<?php

namespace core\game\item\prestige\types;

use core\game\item\prestige\Prestige;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class Inquisitive extends Prestige {

    /**
     * Inquisitive constructor.
     */
    public function __construct() {
        parent::__construct("Inquisitive", Pickaxe::INQUISITIVE, 1.0, 0.4, 9);

    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "0.4% - 1.0% chance to get an extra 10x XP in a bottle";
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return string
     */
    public function getLore(Pickaxe $pickaxe): string {
        $value = $pickaxe->getAttribute($this->getIdentifier());
        return "$value% chance to get an extra 10x XP in a bottle";
    }

    /**
     * @return Item
     */
    public function getDefaultDisplayItem(): Item {
        return VanillaItems::LEAPING_POTION();
    }
}