<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\vanilla\Axe;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class TankEnchantment extends Enchantment {

    /**
     * TankEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::TANK, "Tank", self::LEGENDARY, "Reduces damage taken from axes (Stacks)", self::DAMAGE_BY, self::SLOT_ARMOR, 4);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, &$damage) {
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            if($damager->getInventory()->getItemInHand() instanceof Axe) {
                $damage *= (1 - ($level * 0.025));
            }
        };
    }
}