<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class EternalLuckEnchantment extends Enchantment {

    /**
     * EternalLuckEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ETERNAL_LUCK, "Eternal Luck", self::EXECUTIVE, "Dramatically increases enchant proc rates / decreases enemy proc rates.", self::BREAK, self::SLOT_ALL, 6, self::SLOT_NONE, self::LUCKY);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}