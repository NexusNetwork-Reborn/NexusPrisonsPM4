<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class GuardDeflectEnchantment extends Enchantment {

    /**
     * GuardDeflectEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::GUARD_DEFLECT, "Guard Deflect", self::ULTIMATE, "Reflects incoming damage from cell guards.", self::DAMAGE_BY, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            // TODO
        };
    }
}