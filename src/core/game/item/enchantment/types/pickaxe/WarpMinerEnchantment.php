<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class WarpMinerEnchantment extends Enchantment {

    /**
     * WarpMinerEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::WARP_MINER, "Warp Miner", self::GODLY, "Increases xp gain in the overworld and The Executive Mine.", self::BREAK, self::SLOT_PICKAXE, 5);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}