<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\satchel;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class SnatchEnchantment extends Enchantment {

    /**
     * SnatchEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SNATCH, "Snatch", self::GODLY, "Chance to gain ores on your satchel from surrounding players.", self::BREAK, self::SLOT_SATCHEL, 3);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}