<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageEvent;

class CrouchEnchantment extends Enchantment {

    /**
     * CrouchEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::CROUCH, "Crouch", self::UNCOMMON, "Take less damage while sneaking. (Stackable)", self::DAMAGE_BY_ALL, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            if($entity->isSneaking()) {
                $damage *= (1 - ($level * 0.015));
            }
        };
    }
}