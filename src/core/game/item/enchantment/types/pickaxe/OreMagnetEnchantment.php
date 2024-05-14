<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class OreMagnetEnchantment extends Enchantment {

    /**
     * OreMagnetEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ORE_MAGNET, "Ore Magnet", self::SIMPLE, "Receive more ores from mining.", self::BREAK, self::SLOT_PICKAXE, 5);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}