<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Durable;
use pocketmine\player\Player;

class DisintegrateEnchantment extends Enchantment {

    /**
     * DisintegrateEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::DISINTEGRATE, "Disintegrate", self::ULTIMATE, "Deal more durability damage to your enemies gear.", self::DAMAGE, self::SLOT_AXE, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof Player) {
                return;
            }
            $inventory = $entity->getArmorInventory();
            foreach($inventory->getContents() as $slot => $armor) {
                $d = mt_rand($level, 5) * $level;
                if($armor instanceof Durable) {
                    $armor->applyDamage($d);
                    $inventory->setItem($slot, $armor);
                }
            }
        };
    }
}