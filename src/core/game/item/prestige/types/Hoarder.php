<?php

namespace core\game\item\prestige\types;

use core\game\item\prestige\Prestige;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;

class Hoarder extends Prestige {

    /**
     * Hoarder constructor.
     */
    public function __construct() {
        parent::__construct("Hoarder", Pickaxe::HOARDER, 50, 17);
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "+17% - 50% Ores";
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return string
     */
    public function getLore(Pickaxe $pickaxe): string {
        $value = $pickaxe->getAttribute($this->getIdentifier());
        return "+$value% Ores";
    }

    /**
     * @return Item
     */
    public function getDefaultDisplayItem(): Item {
        return VanillaBlocks::DIAMOND_ORE()->asItem();
    }
}