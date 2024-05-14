<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class FearlessEnchantment extends Enchantment {

    /**
     * FearlessEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::FEARLESS, "Fearless", self::LEGENDARY, "Increases your damage the closer you are to your opponent but decreases it if too far.", self::DAMAGE, self::SLOT_AXE, 5, self::SLOT_SWORD);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $damager = $event->getDamager();
            $entity = $event->getEntity();
            $modifier = 1 - ($level * 0.07);
            $modifier += (0.025 * $level) * (5 - min(5, $damager->getPosition()->distance($entity->getPosition())));
            $damage *= $modifier;
        };
    }
}