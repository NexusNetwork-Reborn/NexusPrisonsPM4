<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class DeflectEnchantment extends Enchantment {

    /**
     * ManeuverEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::DEFLECT, "Deflect", self::ULTIMATE, "Chance to redirect an enemies offensive weapon enchantment.", self::DAMAGE_BY, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
        };
    }
}