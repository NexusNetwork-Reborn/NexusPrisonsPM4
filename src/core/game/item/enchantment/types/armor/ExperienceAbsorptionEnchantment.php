<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\enchantment\ItemFlags;

class ExperienceAbsorptionEnchantment extends Enchantment
{

    public function __construct()
    {
        parent::__construct(self::EXPERIENCE_ABSORPTION,
            "Experience Absorption",
            self::LEGENDARY,
            "Increased XP gain in Overworld and Exec Mine.",
            self::BREAK,
            ItemFlags::ARMOR,
            5
        );

        $this->callable = function (BlockBreakEvent $event, int $level) : void {

        };
    }
}