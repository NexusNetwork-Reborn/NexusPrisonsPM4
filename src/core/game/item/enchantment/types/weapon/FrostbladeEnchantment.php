<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\player\NexusPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class FrostbladeEnchantment extends Enchantment {

    /**
     * PoisonEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::FROSTBLADE, "Frostblade", self::ELITE, "Chance to slow your enemy.", self::DAMAGE, self::SLOT_SWORD, 3, self::SLOT_AXE);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getEffects()->has(VanillaEffects::SLOWNESS())) {
                return;
            }
            $random = mt_rand(1, 175);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $frostblade = $entity;
                $deflect = $entity->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::DEFLECT));
                if($deflect > 0) {
                    if($deflect >= mt_rand(1, 24)) {
                        $frostblade = $damager;
                    }
                }
                $frostblade->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), $level * 40, $level, false));
            }
        };
    }
}