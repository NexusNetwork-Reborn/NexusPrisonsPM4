<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\enchantment\ItemFlags;

class BountyHunterEnchantment extends Enchantment
{

    public function __construct()
    {
        parent::__construct(self::BOUNTY_HUNTER,
            "Bounty Hunter",
            self::GODLY,
            "Gain increased XP for slaying Bandits.",
            self::DAMAGE,
            ItemFlags::SWORD,
            5,
            ItemFlags::AXE
        );

        $this->callable = function (EntityDamageByEntityEvent $event, int $level, float &$damage) : void {

        };
    }
}