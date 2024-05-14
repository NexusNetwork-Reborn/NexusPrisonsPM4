<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Durable;

class MolecularHarvesterEnchantment extends Enchantment {

    /**
     * RejuvenateEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::MOLECULAR_HARVESTER, "Molecular Harvester", self::ELITE, "Chance to heal durability on your armor when attacking.", self::DAMAGE, self::SLOT_SWORD, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            if(mt_rand(1, 10) <= $damager->getCESession()->getItemLuckModifier()) {
                $inventory = $damager->getArmorInventory();
                foreach($inventory->getContents() as $slot => $armor) {
                    if($armor instanceof Durable) {
                        $d = mt_rand($level, 5) * $level;
                        $armor->setDamage(max(0, $armor->getDamage() - $d));
                        $inventory->setItem($slot, $armor);
                    }
                }
            }
        };
    }
}