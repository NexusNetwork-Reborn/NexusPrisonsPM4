<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\level\entity\types\Lightning;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class ElementalMasteryEnchantment extends Enchantment {

    /**
     * ElementalMasteryEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ELEMENTAL_MASTERY, "Elemental Mastery", self::ULTIMATE, "Reduces incoming damage by Lightning and Fire (direct and ticks).", self::DAMAGE_BY_ALL, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            $reduction = $level * 0.025;
            if($event->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK) {
                $damage *= 1 - $reduction;
            }
            if($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if($damager instanceof Lightning) {
                    $damage *= 1 - $reduction;
                }
            }
        };
    }
}