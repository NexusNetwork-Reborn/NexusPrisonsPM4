<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\satchel;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class DoubleDropEnchantment extends Enchantment {

    /**
     * DoubleDropEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::DOUBLE_DROP, "Double Drop", self::LEGENDARY, "Chance to receive double drops in your satchel.", self::BREAK, self::SLOT_SATCHEL, 5);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}