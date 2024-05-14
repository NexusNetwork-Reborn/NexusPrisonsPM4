<?php

namespace core\game\item\prestige\types;

use core\game\item\prestige\Prestige;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class OreExtractor extends Prestige {

    /**
     * OreExtractor constructor.
     */
    public function __construct() {
        parent::__construct("Ore Extractor", Pickaxe::ORE_EXTRACTOR, 0.010, 0.004);

    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "0.004% - 0.010% chance to find a generator";
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return string
     */
    public function getLore(Pickaxe $pickaxe): string {
        $value = $pickaxe->getAttribute($this->getIdentifier());
        return "$value% chance to find a generator";
    }

    /**
     * @return Item
     */
    public function getDefaultDisplayItem(): Item {
        return VanillaBlocks::DIAMOND()->asItem();
    }
}