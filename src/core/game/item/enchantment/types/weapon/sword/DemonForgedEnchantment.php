<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class DemonForgedEnchantment extends Enchantment {

    /**
     * DemonForgedEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::DEMON_FORGED, "Demon Forged", self::ULTIMATE, "Cause more durability damage.", self::DAMAGE, self::SLOT_SWORD, 4);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            $d = mt_rand(2, ($level + 3));
            $entity->damageArmor($d);
        };
    }
}