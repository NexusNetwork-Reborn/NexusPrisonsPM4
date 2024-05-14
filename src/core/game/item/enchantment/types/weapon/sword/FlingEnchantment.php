<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector3;

class FlingEnchantment extends Enchantment {

    /**
     * FlingEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::FLING, "Fling", self::ELITE, "Chance to fling your enemy up into the air.", self::DAMAGE, self::SLOT_SWORD, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            $random = mt_rand(1, 175);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $entity->setMotion(new Vector3(0, 0.75 + (0.25 * $level), 0));
            }
        };
    }
}