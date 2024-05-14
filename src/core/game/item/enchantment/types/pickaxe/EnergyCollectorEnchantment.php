<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class EnergyCollectorEnchantment extends Enchantment {

    /**
     * EnergyCollectorEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ENERGY_COLLECTOR, "Energy Collector", self::UNCOMMON, "Gain additional energy when mining.", self::BREAK, self::SLOT_PICKAXE, 5);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}