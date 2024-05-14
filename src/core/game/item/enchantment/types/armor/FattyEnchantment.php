<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class FattyEnchantment extends Enchantment {

    /**
     * FattyEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::FATTY, "Fatty", self::ULTIMATE, "Partially negates and reflects damage reduction from cell guards. (does not stack)", self::DAMAGE_BY, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            // TODO
        };
    }
}