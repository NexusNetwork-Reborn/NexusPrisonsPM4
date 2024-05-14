<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;

class MeteorHunterEnchantment extends Enchantment {

    /**
     * MeteorHunterEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::METEOR_HUNTER, "Meteor Hunter", self::ULTIMATE, "Chance of receiving double Contrabands and faster mining speed for Meteors.", self::BREAK, self::SLOT_PICKAXE, 5);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            return;
        };
    }
}