<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class CactusEnchantment extends Enchantment {

    /**
     * CactusEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::CACTUS, "Cactus", self::ELITE, "Chance on hit to inflict a percentage of incoming damage to your enemy.", self::DAMAGE_BY, self::SLOT_LEGS, 2);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            $damager = $event->getDamager();
            $random = mt_rand(1, 500);
            $chance = $entity->getCESession()->getArmorLuckModifier();
            if($chance >= $random) {
                $dmg = $damage * (0.35 * $level);
                $ev = new EntityDamageByEntityEvent($entity, $damager, EntityDamageEvent::CAUSE_CUSTOM, $dmg, [], 0);
                $damager->attack($ev);
            }
        };
    }
}