<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\satchel;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class EnergyMirrorEnchantment extends Enchantment {

    /**
     * EnergyMirrorEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ENERGY_MIRROR, "Energy Mirror", self::ULTIMATE, "Chance to gain energy on your satchel while mining.", self::BREAK, self::SLOT_SATCHEL, 6);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}