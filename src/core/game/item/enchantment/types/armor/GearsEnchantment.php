<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\player\PlayerMoveEvent;

class GearsEnchantment extends Enchantment {

    /**
     * GearsEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::GEARS, "Gears", self::ELITE, "Move faster.", self::MOVE, self::SLOT_FEET, 3);
        $this->callable = function(PlayerMoveEvent $event, int $level) {
            $player = $event->getPlayer();
            if(!$player->getEffects()->has(VanillaEffects::SPEED())) {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 100000000, $level - 1, false));
            }
            return;
        };
    }
}