<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class ShardDiscovererEnchantment extends Enchantment {

    /**
     * ShardDiscovererEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SHARD_DISCOVERER, "Shard Discoverer", self::SIMPLE, "Higher chance to discover shards.", self::BREAK, self::SLOT_PICKAXE, 5);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            return;
        };
    }
}