<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class PainkillerEnchantment extends Enchantment {

    /**
     * PainkillerEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::PAINKILLER, "Painkiller", self::ULTIMATE, "Gain resistance temporarily when on low HP.", self::DAMAGE_BY, self::SLOT_ARMOR, 4);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $player = $event->getEntity();
            if($player instanceof NexusPlayer) {
                if($player->getHealth() <= 8) {
                    if((time() - $player->getCESession()->getLastPainkiller()) >= 300) {
                        if(!$player->getEffects()->has(VanillaEffects::RESISTANCE())) {
                            $player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), ($level * 15) * 20, $level, false));
                            $player->getCESession()->setLastPainkiller();
                        }
                    }
                }
            }
        };
    }
}