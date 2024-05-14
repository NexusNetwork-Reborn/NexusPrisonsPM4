<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class AlchemyEnchantment extends Enchantment {

    /**
     * AlchemyEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ALCHEMY, "Alchemy", self::UNCOMMON, "Chance to turn mined ores into money automatically.", self::BREAK, self::SLOT_PICKAXE, 3);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            return;
        };
    }
}