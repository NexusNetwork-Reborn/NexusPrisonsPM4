<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;

class DemonicFrenzyEnchantment extends Enchantment {

    /**
     * DemonicFrenzyEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::DEMONIC_FRENZY, "Demonic Frenzy", self::EXECUTIVE, "Build demonic energy stacks FAST on every hit that boost your damage, resets when attacked (Requires Frenzy 4)", self::DAMAGE, self::SLOT_SWORD, 3, self::SLOT_AXE, self::FRENZY);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            if($event->isCancelled()) {
                return;
            }
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            $damager->getCESession()->addFrenzyHits();
            $hits = $damager->getCESession()->getFrenzyHits();
            $cap = 1;
            $percent = round($hits * (0.12 + (0.02 * $level)), 2);
            $percent = $percent < $cap ? $percent : $cap;
            if($cap !== $percent) {
                $showPercentage = $percent * 100;
                $damager->addCEPopup(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "** DEMONIC FRENZY [" . TextFormat::RESET . TextFormat::GRAY . "+$showPercentage%%%" . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "] **");
            }
            $damage *= 1 + $percent;
        };
    }
}