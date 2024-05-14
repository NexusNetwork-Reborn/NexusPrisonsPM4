<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class AntiGankEnchantment extends Enchantment {

    /**
     * AntiGankEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ANTI_GANK, "Anti Gank", self::ULTIMATE, "Deal more damage the more enemy players are close to you.", self::DAMAGE, self::SLOT_AXE, 5, self::SLOT_SWORD);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $damager = $event->getDamager();
            $bb = $damager->getBoundingBox()->expandedCopy(10, 10, 10);
            $world = $damager->getWorld();
            if($world === null) {
                return;
            }
            $modifier = 1;
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            $count = 0;
            $gang = $damager->getDataSession()->getGang();
            foreach($world->getNearbyEntities($bb) as $e) {
                if($e->getId() === $damager->getId()) {
                    continue;
                }
                if($e instanceof NexusPlayer) {
                    if($gang !== null and $gang->isInGang($e->getName())) {
                        continue;
                    }
                    $count++;
                }
            }
            if($count > 1) {
                $modifier += $count * (0.025 * $level);
            }
            $damage *= $modifier;
        };
    }
}