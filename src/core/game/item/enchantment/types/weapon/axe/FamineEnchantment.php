<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class FamineEnchantment extends Enchantment {

    /**
     * FamineEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::FAMINE, "Famine", self::ELITE, "Chance to reduce your opponents saturation or hunger level.", self::DAMAGE, self::SLOT_AXE, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getEffects()->has(VanillaEffects::HUNGER())) {
                return;
            }
            $random = mt_rand(1, 200);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $entity->getEffects()->add(new EffectInstance(VanillaEffects::HUNGER(), $level * 20, $level, false));
            }
        };
    }
}