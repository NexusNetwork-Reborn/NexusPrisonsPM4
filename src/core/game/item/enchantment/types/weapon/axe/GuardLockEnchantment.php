<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class GuardLockEnchantment extends Enchantment {

    /**
     * GuardLockEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::GUARD_LOCK, "Guard Lock", self::ELITE, "Chance to stun a cell guard in place.", self::DAMAGE, self::SLOT_AXE, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            // TODO
        };
    }
}