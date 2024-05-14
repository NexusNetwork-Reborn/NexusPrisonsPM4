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

class FootrotEnchantment extends Enchantment {

    /**
     * FootrotEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::FOOTROT, "Footrot", self::ULTIMATE, "Chance to apply slowness affect.", self::DAMAGE, self::SLOT_BOW, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($event->getCause() !== EntityDamageByEntityEvent::CAUSE_PROJECTILE) {
                return;
            }
            if($entity->getEffects()->has(VanillaEffects::SLOWNESS())) {
                return;
            }
            $random = mt_rand(1, 30);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $entity->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), $level * 60, 1, false));
                $entity->addCEPopup(TextFormat::YELLOW . TextFormat::BOLD . "* FOOTROT [" . TextFormat::RESET . TextFormat::GRAY . "SLOWNESS " . EnchantmentManager::getRomanNumber(2) . ", ". number_format($level * 3, 1) . "s" . TextFormat::YELLOW . TextFormat::BOLD . "] *");
                $damager->addCEPopup(TextFormat::YELLOW . TextFormat::BOLD . "* FOOTROT [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . ", ". number_format($level * 3, 1) . "s" . TextFormat::YELLOW . TextFormat::BOLD . "] *");
            }
            return;
        };
    }
}