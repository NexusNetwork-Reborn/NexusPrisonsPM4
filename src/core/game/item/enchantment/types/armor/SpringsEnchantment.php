<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\player\PlayerMoveEvent;

class SpringsEnchantment extends Enchantment {

    /**
     * HopsEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SPRINGS, "Springs", self::ELITE, "Obtain jump boost.", self::MOVE, self::SLOT_FEET, 3);
        $this->callable = function(PlayerMoveEvent $event, int $level) {
            $player = $event->getPlayer();
            if(!$player->getEffects()->has(VanillaEffects::JUMP_BOOST())) {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 100000000, $level, false));
            }
            return;
        };
    }
}