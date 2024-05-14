<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class EnrageEnchantment extends Enchantment {

    /**
     * EnrageEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ENRAGE, "Enrage", self::ULTIMATE, "Deal more damage the closer you are to death.", self::DAMAGE, self::SLOT_SWORD, 2, self::SLOT_AXE);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $damager = $event->getDamager();
            if(!$damager instanceof Living) {
                return;
            }
            $add = ((($level / 9) * (20 - $damager->getHealth())) - 2) * 0.65;
            if($add > 0) {
                $damage += $add;
            }
        };
    }
}