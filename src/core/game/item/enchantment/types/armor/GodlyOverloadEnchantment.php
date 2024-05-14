<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\player\PlayerMoveEvent;

class GodlyOverloadEnchantment extends Enchantment {

    /**
     * GodlyOverloadEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::GODLY_OVERLOAD, "Godly Overload", self::EXECUTIVE, "Gain extra, EXTRA hearts! (Requires Overload 4)", self::MOVE, self::SLOT_ARMOR, 3, self::SLOT_NONE, self::OVERLOAD);
        $this->callable = function(PlayerMoveEvent $event, int $level) {
            return;
        };
    }
}