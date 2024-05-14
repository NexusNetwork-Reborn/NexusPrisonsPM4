<?php

namespace core\game\item\prestige\types;

use core\game\item\prestige\Prestige;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class ForgeMaster extends Prestige {

    /**
     * ForgeMaster constructor.
     */
    public function __construct() {
        parent::__construct("Forge Master", Pickaxe::FORGE_MASTER, 10, 30);

    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "+10% - 30% chance to find Forge Fuel (Requires Player Prestige 1)";
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return string
     */
    public function getLore(Pickaxe $pickaxe): string {
        $value = $pickaxe->getAttribute($this->getIdentifier());
        return "+$value% chance to find Forge Fuel (Requires Player Prestige 1)";
    }

    /**
     * @return Item
     */
    public function getDefaultDisplayItem(): Item {
        return VanillaBlocks::COAL()->asItem();
    }
}