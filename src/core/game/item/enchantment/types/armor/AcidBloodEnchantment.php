<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class AcidBloodEnchantment extends Enchantment {

    /**
     * AcidBloodEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ACID_BLOOD, "Acid Blood", self::ELITE, "While low health your blood becomes poisonous to the touch, dealing poison damage to anyone who attacks you", self::DAMAGE_BY, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $player = $event->getEntity();
            $damager = $event->getDamager();
            if($player instanceof NexusPlayer and $damager instanceof NexusPlayer) {
                if($player->getHealth() <= 10) {
                    $random = mt_rand(1, 350);
                    $chance = $level * $player->getCESession()->getArmorLuckModifier();
                    if($chance >= $random) {
                        if(!$damager->getEffects()->has(VanillaEffects::POISON())) {
                            $damager->getEffects()->add(new EffectInstance(VanillaEffects::POISON(), ($level + 3) * 20, 0, false));
                        }
                    }
                }
            }
            return;
        };
    }
}