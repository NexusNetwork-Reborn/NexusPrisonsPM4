<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class ExecuteEnchantment extends Enchantment {

    /**
     * ExecuteEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::EXECUTE, "Execute", self::ULTIMATE, "Deal more damage the closer your enemy is to death.", self::DAMAGE, self::SLOT_SWORD, 5, self::SLOT_AXE);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof Living) {
                return;
            }
            $add = ((($level / 9) * (20 - $entity->getHealth())) - 2) * 0.65;
            if($add > 0) {
                $damage += $add;
            }
        };
    }
}