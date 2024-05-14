<?php

namespace core\game\item\prestige\types;

use core\game\item\prestige\Prestige;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class EnergyMastery extends Prestige {

    /**
     * EnergyMastery constructor.
     */
    public function __construct() {
        parent::__construct("Energy Mastery", Pickaxe::ENERGY_MASTERY, 6, 2);
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "+2 - 6 Charge Orb slots";
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return string
     */
    public function getLore(Pickaxe $pickaxe): string {
        $value = $pickaxe->getAttribute($this->getIdentifier());
        return "+$value Charge Orb slots";
    }

    /**
     * @return Item
     */
    public function getDefaultDisplayItem(): Item {
        return VanillaItems::LIGHT_BLUE_DYE();
    }
}