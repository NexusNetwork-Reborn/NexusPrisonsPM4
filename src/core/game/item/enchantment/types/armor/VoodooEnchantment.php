<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class VoodooEnchantment extends Enchantment {

    /**
     * VoodooEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::VOODOO, "Voodoo", self::LEGENDARY, "Has a chance to give your attacker weakness.", self::DAMAGE_BY, self::SLOT_TORSO, 4);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $player = $event->getEntity();
            $damager = $event->getDamager();
            if($player instanceof NexusPlayer) {
                $random = mt_rand(1, 150);
                $chance = $level * $player->getCESession()->getArmorLuckModifier();
                if($chance >= $random) {
                    if(!$damager->getEffects()->has(VanillaEffects::WEAKNESS())) {
                        $damager->getEffects()->add(new EffectInstance(VanillaEffects::WEAKNESS(), ($level * 5) * 20, 1, false));
                    }
                }
            }
            return;
        };
    }
}