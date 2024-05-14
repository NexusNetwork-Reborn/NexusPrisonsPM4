<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\game\item\mask\Mask;
use core\game\item\types\vanilla\Armor;
use core\level\entity\types\Lightning;
use core\player\NexusPlayer;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class DivineLightningEnchantment extends Enchantment {

    /**
     * DivineLightningEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::DIVINE_LIGHTNING, "Divine Lightning", self::ENERGY, "Your attacks deal AOE lightning damage (costs 70k energy per swing)", self::DAMAGE, self::SLOT_SWORD, 4);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            $helm = $entity->getArmorInventory()->getHelmet();
            if ($helm instanceof Armor && $helm->hasMask(Mask::FIREFLY)) {
                return;
            }
            $world = $damager->getWorld();
            if($world === null) {
                return;
            }
            if($damager->payEnergy(70000)) {
                $random = mt_rand(1, 150);
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
                        $lightning->setStrikes($level);
                        $lightning->setDamageDone(8);
                    }
                }
            }
        };
    }
}