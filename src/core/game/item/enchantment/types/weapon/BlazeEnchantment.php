<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class BlazeEnchantment extends Enchantment {

    /**
     * BlazeEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::BLAZE, "Blaze", self::ELITE, "Chance to send enemies nearby into a blaze.", self::DAMAGE, self::SLOT_SWORD, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            $bb = $damager->getBoundingBox()->expandedCopy(15, 15, 15);
            $world = $damager->getWorld();
            if($world === null) {
                return;
            }
            $random = mt_rand(1, 175);
            $gang = $damager->getDataSession()->getGang();
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                foreach($world->getNearbyEntities($bb) as $e) {
                    if($e->getId() === $damager->getId()) {
                        continue;
                    }
                    if($e->isOnFire()) {
                        continue;
                    }
                    if(!$e instanceof NexusPlayer) {
                        continue;
                    }
                    if($gang !== null and $gang->isInGang($e->getName())) {
                        continue;
                    }
                    if($e->getWorld()->getFolderName() === "boss" || $e->getWorld()->getFolderName() === "executive") {
                        continue;
                    }
                    $e->setOnFire($level * 4);
                }
            }
        };
    }
}