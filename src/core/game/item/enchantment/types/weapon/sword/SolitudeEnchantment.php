<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class SolitudeEnchantment extends Enchantment {

    /**
     * SolitudeEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SOLITUDE, "Solitude", self::LEGENDARY, "Increases the chance of silence proccing.", self::DAMAGE, self::SLOT_SWORD, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            return;
        };
    }
}