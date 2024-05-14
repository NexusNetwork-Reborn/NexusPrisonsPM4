<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class DeepWoundsEnchantment extends Enchantment {

    /**
     * DeepWoundsEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::DEEP_WOUNDS, "Deep Wounds", self::ELITE, "Increases the duration of the Bleed enchants effect.", self::DAMAGE, self::SLOT_AXE, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
        };
    }
}