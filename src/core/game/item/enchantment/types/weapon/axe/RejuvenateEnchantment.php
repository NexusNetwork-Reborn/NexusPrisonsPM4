<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Durable;

class RejuvenateEnchantment extends Enchantment {

    /**
     * RejuvenateEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::REJUVENATE, "Rejuvenate", self::LEGENDARY, "Chance to regain durability on your armor.", self::DAMAGE, self::SLOT_AXE, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            if(mt_rand(1, 10) <= $damager->getCESession()->getItemLuckModifier()) {
                $inventory = $damager->getArmorInventory();
                if($inventory === null) {
                    return;
                }
                $index = mt_rand(0, 3);
                $armor = $inventory->getItem($index);
                $d = mt_rand($level, 10) * $level;
                if($armor instanceof Durable) {
                    $armor->setDamage(max(0, $armor->getDamage() - $d));
                    $inventory->setItem($index, $armor);
                }
            }
        };
    }
}