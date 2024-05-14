<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;

class BerserkEnchantment extends Enchantment {

    /**
     * BerserkEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::BERSERK, "Berserk", self::LEGENDARY, "Chance of Strength and Confusion.", self::DAMAGE, self::SLOT_AXE, 5);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            if($damager->getEffects()->has(VanillaEffects::STRENGTH()) and $damager->getEffects()->has(VanillaEffects::NAUSEA())) {
                return;
            }
            $random = mt_rand(1, 200);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $amp = 0;
                if($level >= 4) {
                    $amp = 1;
                }
                $damager->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), ($level + 1) * 20, $amp, false));
                $damager->getEffects()->add(new EffectInstance(VanillaEffects::NAUSEA(), ($level + 1) * 20, 0, false));
                $damager->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* BERSERK  *");
                $entity->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* BERSERK [" . TextFormat::RESET . TextFormat::GRAY . $damager->getName() . TextFormat::GOLD . TextFormat::BOLD . "] *");
            }
        };
    }
}