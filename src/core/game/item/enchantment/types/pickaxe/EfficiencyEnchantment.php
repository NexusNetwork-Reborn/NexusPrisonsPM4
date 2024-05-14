<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class EfficiencyEnchantment extends Enchantment {

    /**
     * EfficiencyEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::EFFICIENCY2, "Efficiency", self::SIMPLE, "Increase your mining speed.", self::BREAK, self::SLOT_PICKAXE, 6);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            return;
        };
    }
}