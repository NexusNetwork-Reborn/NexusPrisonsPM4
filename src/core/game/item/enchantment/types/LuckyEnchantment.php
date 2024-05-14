<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class LuckyEnchantment extends Enchantment {

    /**
     * LuckyEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::LUCKY, "Lucky", self::LEGENDARY, "Increases the proc rate of other enchants on the same item.", self::BREAK, self::SLOT_ALL, 4);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}