<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\ItemFlags;

class LeviathanBloodEnchantment extends Enchantment
{

    public function __construct()
    {
        parent::__construct(self::LEVIATHAN_BLOOD,
            "Leviathan Blood",
            self::EXECUTIVE,
            "Reduces ALL incoming damage, tick damage, and enemy Frenzy stacks gained on hit (Requires Titan Blood 4)",
            self::DAMAGE_BY_ALL,
            ItemFlags::ARMOR,
            3,
            ItemFlags::ARMOR,
            self::TITAN_BLOOD
        );

        $this->callable = function (EntityDamageEvent $event, int $level, float &$damage) : void {
            $damage *= (1 - ($level * 0.025));
            return;
        };
    }
}