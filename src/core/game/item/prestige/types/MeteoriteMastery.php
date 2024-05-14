<?php

namespace core\game\item\prestige\types;

use core\game\item\prestige\Prestige;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class MeteoriteMastery extends Prestige {

    /**
     * MeteoriteMastery constructor.
     */
    public function __construct() {
        parent::__construct("Meteorite Mastery", Pickaxe::METEORITE_MASTERY, 20, 7);
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "+7% - 20% ores from Meteorites";
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return string
     */
    public function getLore(Pickaxe $pickaxe): string {
        $value = $pickaxe->getAttribute($this->getIdentifier());
        return "+$value% ores from Meteorites";
    }

    /**
     * @return Item
     */
    public function getDefaultDisplayItem(): Item {
        return VanillaBlocks::COAL_ORE()->asItem();
    }
}