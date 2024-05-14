<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class AntiGuardEnchantment extends Enchantment {

    /**
     * AntiGuardEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ANTI_GUARD, "Anti Guard", self::ULTIMATE, "Partially negates damage from cell guards. (does not stack)", self::DAMAGE_BY, self::SLOT_ARMOR, 4);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            // TODO
        };
    }
}