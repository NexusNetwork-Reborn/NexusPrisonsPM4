<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class ReplenishEnchantment extends Enchantment {

    /**
     * ReplenishEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::REPLENISH, "Replenish", self::ELITE, "Chance to replenish ores that you mine. (Does not work on generators.)", self::BREAK, self::SLOT_PICKAXE, 5);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            return;
        };
    }
}