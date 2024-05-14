<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class LeechEnchantment extends Enchantment {

    /**
     * ArmoredEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::LEECH, "Leech", self::UNCOMMON, "Gain additional energy from Bandit Kills.", self::DAMAGE, self::SLOT_ARMOR, 5);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
        };
    }
}