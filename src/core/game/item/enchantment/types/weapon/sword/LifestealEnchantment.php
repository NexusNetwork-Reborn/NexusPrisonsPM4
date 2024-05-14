<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\utils\TextFormat;

class LifestealEnchantment extends Enchantment {

    /**
     * LifeStealEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::LIFESTEAL, "Lifesteal", self::LEGENDARY, "Heals for a portion of the damage you deal.", self::DAMAGE, self::SLOT_SWORD, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $damager = $event->getDamager();
            $maxHealth = $damager->getMaxHealth();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            if($damager->getHealth() === $maxHealth) {
                return;
            }
            if($event->isCancelled()) {
                return;
            }
            $random = mt_rand(1, 175);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $life = 1 + round(($event->getFinalDamage() * (0.25 * $level)), 1);
                $damager->heal(new EntityRegainHealthEvent($damager, $life, EntityRegainHealthEvent::CAUSE_CUSTOM));
                $damager->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* LIFESTEAL [" . TextFormat::RESET . TextFormat::WHITE . number_format($life, 1) . TextFormat::RED . TextFormat::BOLD . " HP" . TextFormat::GOLD . "] *");
            }
        };
    }
}