<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\bow;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\player\NexusPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;

class MalnourishedEnchantment extends Enchantment {

    /**
     * MalnourishedEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::MALNOURISHED, "Malnourished", self::LEGENDARY, "Chance to apply hunger affect.", self::DAMAGE, self::SLOT_BOW, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($event->getCause() !== EntityDamageByEntityEvent::CAUSE_PROJECTILE) {
                return;
            }
            if($entity->getEffects()->has(VanillaEffects::HUNGER())) {
                return;
            }
            $random = mt_rand(1, 30);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $entity->getEffects()->add(new EffectInstance(VanillaEffects::HUNGER(), $level * 60, $level + 1, false));
                $entity->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* MALNOURISHED [" . TextFormat::RESET . TextFormat::GRAY . "HUNGER " . EnchantmentManager::getRomanNumber($level + 2) . ", ". number_format($level * 3, 1) . "s" . TextFormat::GOLD . TextFormat::BOLD . "] *");
                $damager->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* MALNOURISHED [" . $entity->getName() . ", ". number_format($level * 3, 1) . "s" . TextFormat::GOLD . TextFormat::BOLD . "] *");
            }
            return;
        };
    }
}