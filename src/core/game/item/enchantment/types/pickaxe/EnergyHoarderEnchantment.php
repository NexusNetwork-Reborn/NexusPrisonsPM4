<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class EnergyHoarderEnchantment extends Enchantment {

    /**
     * EnergyHoarderEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ENERGY_HOARDER, "Energy Hoarder", self::EXECUTIVE, "Gain ludicrous additional energy when mining (Required Energy Collector 5)", self::BREAK, self::SLOT_PICKAXE, 5, self::SLOT_NONE, self::ENERGY_COLLECTOR);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}