<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class BloodMoneyEnchantment extends Enchantment {

    /**
     * ArmoredEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::BLOOD_MONEY, "Blood Money", self::SIMPLE, "Gain additional $$ from Bandit Kills.", self::DAMAGE, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
        };
    }
}