<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageEvent;

class HardenedEnchantment extends Enchantment {

    /**
     * HardenedEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::HARDEN, "Hardened", self::ULTIMATE, "Increases durability on gear.", self::DAMAGE, self::SLOT_ALL, 3);
        $this->callable = function(EntityDamageEvent $event, int $level) {
        };
    }
}