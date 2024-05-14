<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class TimeWarpEnchantment extends Enchantment {

    /**
     * TimeWarpEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::TIME_WARP, "Time Warp", self::EXECUTIVE, "Greatly increases xp gain in the overworld and The Executive Mine. (Requires Warp Miner 5)", self::BREAK, self::SLOT_PICKAXE, 3, self::SLOT_NONE, self::WARP_MINER);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}