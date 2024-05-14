<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class EnrichEnchantment extends Enchantment {

    /**
     * EnrichEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ENRICH, "Enrich", self::ELITE, "Increase your chances receiving minerals.", self::BREAK, self::SLOT_PICKAXE, 5);
        $this->callable = function(BlockBreakEvent $event, int $level) {
        };
    }
}