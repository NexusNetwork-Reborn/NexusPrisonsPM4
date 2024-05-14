<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class DragonForgedEnchantment extends Enchantment {

    /**
     * DemonForgedEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::DRAGON_FORGED, "Dragon Forged", self::EXECUTIVE, "Cause MASSIVE durability damage to your enemies gear and deal extra damage to players with missing gear (Requires Demon Forged 4)", self::DAMAGE, self::SLOT_SWORD, 3, self::SLOT_NONE, self::DEMON_FORGED);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            $d = mt_rand(2, 4 + ($level * 2));
            $entity->damageArmor($d);
            $count = count($entity->getArmorInventory()->getContents());
            $damage *= 1 + (0.15 * (4 - $count));
        };
    }
}