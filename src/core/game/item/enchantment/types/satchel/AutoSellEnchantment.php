<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\satchel;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class AutoSellEnchantment extends Enchantment {

    /**
     * AutoSellEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::AUTO_SELL, "Auto Sell", self::UNCOMMON, "Chance to auto-sell ores that would have gone in your satchel.", self::BREAK, self::SLOT_SATCHEL, 3);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}