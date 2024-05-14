<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\vanilla\Sword;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class AxemanEnchantment extends Enchantment {

    /**
     * AxemanEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::AXEMAN, "Axeman", self::ELITE, "Deal more damage to players wielding swords.", self::DAMAGE, self::SLOT_AXE, 5);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getInventory()->getItemInHand() instanceof Sword) {
                $damage *= (1 + ($level * 0.1));
            }
        };
    }
}