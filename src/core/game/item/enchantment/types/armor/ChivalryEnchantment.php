<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class ChivalryEnchantment extends Enchantment {

    /**
     * ChivalryEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::CHIVALRY, "Chivalry", self::ULTIMATE, "The lower your health, the less durability damage you receive.", self::DAMAGE_BY, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            return;
        };
    }
}