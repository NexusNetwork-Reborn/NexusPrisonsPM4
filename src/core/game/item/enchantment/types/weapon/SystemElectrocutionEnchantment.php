<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\level\entity\types\Lightning;
use core\player\NexusPlayer;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class SystemElectrocutionEnchantment extends Enchantment {

    /**
     * SystemElectrocutionEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SYSTEM_ELECTROCUTION, "System Electrocution", self::EXECUTIVE, "Chance to send speeding bolts of electricity through your enemies causing crippling damage (Requires Lightning 3)", self::DAMAGE, self::SLOT_SWORD, 3, self::SLOT_AXE, self::LIGHTNING);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            $world = $damager->getWorld();
            if($world === null) {
                return;
            }
            $random = mt_rand(1, 350);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $gang = $damager->getDataSession()->getGang();
                $bb = $damager->getBoundingBox()->expandedCopy(15, 15, 15);
                foreach($world->getNearbyEntities($bb) as $e) {
                    if(!$e instanceof NexusPlayer) {
                        continue;
                    }
                    if($e->getId() === $damager->getId()) {
                        continue;
                    }
                    if($gang !== null and $gang->isInGang($e->getName())) {
                        continue;
                    }
                    $pos = $e->getPosition();
                    $lightning = new Lightning(new Location($pos->x, $pos->y, $pos->z, $pos->getWorld(), 0, 0));
                    $lightning->spawnToAll();
                    $lightning->setOwningEntity($damager);
                    $lightning->setTargetEntity($e);
                    $lightning->setStrikes(($level * 2) + 2);
                    $lightning->setDamageDone(7);
                }
            }
        };
    }
}