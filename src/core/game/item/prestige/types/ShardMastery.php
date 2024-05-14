<?php

namespace core\game\item\prestige\types;

use core\game\item\prestige\Prestige;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class ShardMastery extends Prestige {

    /**
     * ShardMastery constructor.
     */
    public function __construct() {
        parent::__construct("Shard Mastery", Pickaxe::SHARD_MASTERY, 2.5, 0.8);

    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "0.8 - 2.5x Shard chance";
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return string
     */
    public function getLore(Pickaxe $pickaxe): string {
        $value = $pickaxe->getAttribute($this->getIdentifier());
        return "$value" . "x Shard chance";
    }

    /**
     * @return Item
     */
    public function getDefaultDisplayItem(): Item {
        return VanillaItems::PRISMARINE_SHARD();
    }
}