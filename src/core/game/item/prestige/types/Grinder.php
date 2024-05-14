<?php

namespace core\game\item\prestige\types;

use core\game\item\prestige\Prestige;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class Grinder extends Prestige {

    /**
     * Grinder constructor.
     */
    public function __construct() {
        parent::__construct("Grinder", Pickaxe::GRINDER, 30, 10);
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "+10% - 30% Mining speed";
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return string
     */
    public function getLore(Pickaxe $pickaxe): string {
        $value = $pickaxe->getAttribute($this->getIdentifier());
        return "+$value% Mining speed";
    }

    /**
     * @return Item
     */
    public function getDefaultDisplayItem(): Item {
        return VanillaItems::GLOWSTONE_DUST();
    }
}