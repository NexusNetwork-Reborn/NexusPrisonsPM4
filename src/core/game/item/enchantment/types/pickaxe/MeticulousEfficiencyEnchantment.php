<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class MeticulousEfficiencyEnchantment extends Enchantment {

    /**
     * MeticulousEfficiencyEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::METICULOUS_EFFICIENCY, "Meticulous Efficiency", self::EXECUTIVE, "Faster mining speed and a chance to extract more ores. (Requires Efficiency 6)", self::BREAK, self::SLOT_PICKAXE, 5, self::SLOT_NONE, self::EFFICIENCY2);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            return;
        };
    }
}