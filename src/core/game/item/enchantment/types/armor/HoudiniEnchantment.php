<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class HoudiniEnchantment extends Enchantment {

    /**
     * HoudiniEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::HOUDINI, "Houdini", self::ENERGY, "Reduces the duration of Trap enchants with a chance to fully negate or reflect it back. (costs 500k-1.5M energy upon activation)", self::DAMAGE_BY, self::SLOT_HEAD, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
        };
    }
}