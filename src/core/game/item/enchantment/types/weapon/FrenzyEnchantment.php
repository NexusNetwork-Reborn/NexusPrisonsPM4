<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;

class FrenzyEnchantment extends Enchantment {

    /**
     * FrenzyEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::FRENZY, "Frenzy", self::LEGENDARY, "Build demonic energy stacks on every hit that boost your damage, resets when attacked.", self::DAMAGE, self::SLOT_SWORD, 4, self::SLOT_AXE);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            if($event->isCancelled()) {
                return;
            }

            $damager = $event->getDamager();
            $victim = $event->getEntity();

            if(!$damager instanceof NexusPlayer) {
                return;
            }

            if($victim instanceof NexusPlayer) {
                foreach ($victim->getArmorInventory()->getContents() as $armor) {
                    $level = $armor->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::LEVIATHAN_BLOOD));

                    if($level > 0) break;
                }
            }

            if($victim instanceof NexusPlayer && isset($level) && $level * $victim->getCESession()->getArmorLuckModifier() > mt_rand(1, 500)) {
                $victim->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* [" . TextFormat::RED . "LEVIATHAN BLOOD" . TextFormat::BOLD . TextFormat::GRAY . "* ]");
            } else {
                $damager->getCESession()->addFrenzyHits();
            }

            $hits = $damager->getCESession()->getFrenzyHits();
            $cap = 1;
            $percent = round($hits * (0.03 * $level), 2);
            $percent = $percent < $cap ? $percent : $cap;
            if($cap !== $percent) {
                $showPercentage = $percent * 100;
                $damager->addCEPopup(TextFormat::BOLD . TextFormat::GOLD . "* FRENZY [" . TextFormat::RESET . TextFormat::GRAY . "+$showPercentage%%%" . TextFormat::GOLD . TextFormat::BOLD . "] *");
            }
            $damage *= 1 + $percent;
        };
    }
}