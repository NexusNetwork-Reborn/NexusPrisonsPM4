<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\player\PlayerMoveEvent;

class OverloadEnchantment extends Enchantment {

    /**
     * OverloadEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::OVERLOAD, "Overload", self::LEGENDARY, "Gain extra hearts.", self::MOVE, self::SLOT_ARMOR, 4);
        $this->callable = function(PlayerMoveEvent $event, int $level) {
            return;
        };
    }
}