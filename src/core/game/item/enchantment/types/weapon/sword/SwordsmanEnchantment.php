<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\vanilla\Axe;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class SwordsmanEnchantment extends Enchantment {

    /**
     * SwordsmanEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SWORDSMAN, "Swordsman", self::ELITE, "Deal more damage to players wielding axes.", self::DAMAGE, self::SLOT_SWORD, 5);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getInventory()->getItemInHand() instanceof Axe) {
                $damage *= (1 + ($level * 0.1));
            }
        };
    }
}