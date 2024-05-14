<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\ItemIds;

class TransfuseEnchantment extends Enchantment {

    const ITEMS = [
        ItemIds::COAL_ORE => ItemIds::IRON_ORE,
        ItemIds::IRON_ORE => ItemIds::LAPIS_ORE,
        ItemIds::LAPIS_ORE => ItemIds::REDSTONE_ORE,
        ItemIds::REDSTONE_ORE => ItemIds::GOLD_ORE,
        ItemIds::GOLD_ORE => ItemIds::DIAMOND_ORE,
        ItemIds::DIAMOND_ORE => ItemIds::EMERALD_ORE,
        ItemIds::COAL => ItemIds::IRON_INGOT,
        ItemIds::IRON_INGOT => ItemIds::REDSTONE,
        ItemIds::REDSTONE => ItemIds::GOLD_INGOT,
        ItemIds::GOLD_INGOT => ItemIds::DIAMOND,
        ItemIds::DIAMOND => ItemIds::EMERALD
    ];

    /**
     * TransfuseEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::TRANSFUSE, "Transfuse", self::UNCOMMON, "Chance to turn mined materials you mine into an upper tier.", self::BREAK, self::SLOT_PICKAXE, 3);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            return;
        };
    }
}