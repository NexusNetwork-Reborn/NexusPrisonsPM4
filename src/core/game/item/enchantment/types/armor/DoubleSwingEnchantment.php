<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\player\PlayerEvent;

use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\item\enchantment\ItemFlags;

class DoubleSwingEnchantment extends Enchantment
{

    public function __construct()
    {
        parent::__construct(self::DOUBLE_SWING,
            "Double Swing",
            self::LEGENDARY,
            "Chance to gain double efficiency while mining.",
            self::INTERACT,
            ItemFlags::SWORD,
            4
        );

        $this->callable = function (PlayerEvent $event, int $level) : void  {
            if ($event instanceof PlayerInteractEvent){
                // note: following code added by Azzi

                if (mt_rand(1, 99) <= ($level)){
                    $event->getPlayer()->getEffects()->add(new EffectInstance(VanillaEffects::HASTE(), 40, 3));
                }
            }
        };
    }
}