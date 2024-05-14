<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageEvent;

class RockHardEnchantment extends Enchantment {

    /**
     * RockHardEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ROCK_HARD, "Rock Hard", self::EXECUTIVE, "Massively increased durability on gear.", self::DAMAGE, self::SLOT_ALL, 5, self::SLOT_NONE, self::HARDEN);
        $this->callable = function(EntityDamageEvent $event, int $level) {
        };
    }
}