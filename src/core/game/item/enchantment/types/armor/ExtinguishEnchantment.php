<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageEvent;

class ExtinguishEnchantment extends Enchantment {

    /**
     * ExtinguishEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::EXTINGUISH, "Extinguish", self::ELITE, "Chance with every tick of fire damage to put out the fire.", self::DAMAGE_BY_ALL, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageEvent $event, int $level, float &$damage) {
            $level = min(3, $level);
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            $random = mt_rand(1, 75);
            $chance = $level * $entity->getCESession()->getArmorLuckModifier();
            if($chance >= $random and $entity->isOnFire()) {
                $entity->extinguish();
            }
            return;
        };
    }
}