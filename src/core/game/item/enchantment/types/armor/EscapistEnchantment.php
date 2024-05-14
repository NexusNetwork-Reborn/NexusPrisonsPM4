<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class EscapistEnchantment extends Enchantment {

    /**
     * EscapistEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ESCAPIST, "Escapist", self::ULTIMATE, "Reduces duration of enemy Trap.", self::DAMAGE_BY, self::SLOT_ARMOR, 5);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
        };
    }
}