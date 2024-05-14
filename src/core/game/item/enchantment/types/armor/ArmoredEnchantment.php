<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\vanilla\Sword;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class ArmoredEnchantment extends Enchantment {

    /**
     * ArmoredEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ARMORED, "Armored", self::LEGENDARY, "Reduces damage taken from swords", self::DAMAGE_BY, self::SLOT_TORSO, 4);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            if($damager->getInventory()->getItemInHand() instanceof Sword) {
                $damage *= (1 - ($level * 0.025));
            }
        };
    }
}