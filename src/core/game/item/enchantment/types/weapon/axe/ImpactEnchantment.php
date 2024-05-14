<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Durable;

class ImpactEnchantment extends Enchantment {

    /**
     * ImpactEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::IMPACT, "Impact", self::ULTIMATE, "Chance to deal durability damage to a random piece of your opponents gear.", self::DAMAGE, self::SLOT_AXE, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            if(mt_rand(1, 30) <= $damager->getCESession()->getItemLuckModifier()) {
                $inventory = $entity->getArmorInventory();
                if($inventory === null) {
                    return;
                }
                $index = mt_rand(0, 3);
                $armor = $inventory->getItem($index);
                $d = mt_rand($level, 15) * $level;
                if($armor instanceof Durable) {
                    $armor->applyDamage($d);
                    $inventory->setItem($index, $armor);
                }
            }
        };
    }
}