<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageEvent;

class DamageLimiterEnchantment extends Enchantment {

    /**
     * DamageLimiterEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::DAMAGE_LIMITER, "Damage Limiter", self::ULTIMATE, "Upon proc, the next attack dealt to you will be limited to half of the proccing attack.", self::DAMAGE_BY_ALL, self::SLOT_ARMOR, 6);
        $this->callable = function(EntityDamageEvent $event, int $level, float &$damage) {
            $level = min(6, $level);
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            $random = mt_rand(1, 500);
            $chance = min(25, $level * $entity->getCESession()->getArmorLuckModifier());
            if($chance >= $random) {
                $damage *= 0.5;
            }
        };
    }
}