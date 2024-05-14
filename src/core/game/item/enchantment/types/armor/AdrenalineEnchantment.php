<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\player\NexusPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;

class AdrenalineEnchantment extends Enchantment {

    /**
     * AdrenalineEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ADRENALINE, "Adrenaline", self::ELITE, "When taken to low health you gain a temporary movement speed boost.", self::DAMAGE_BY, self::SLOT_FEET, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $player = $event->getEntity();
            $damager = $event->getDamager();
            if($player instanceof NexusPlayer and $damager instanceof NexusPlayer) {
                if($player->getHealth() <= 8) {
                    if((time() - $player->getCESession()->getLastAdrenaline()) >= 180) {
                        if(!$player->getEffects()->has(VanillaEffects::SPEED())) {
                            $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), ($level + 2) * 20, $level, false));
                            $player->addCEPopup(TextFormat::BLUE . TextFormat::BOLD . "* ADRENALINE [" . TextFormat::RESET . TextFormat::GRAY . "SPEED " . EnchantmentManager::getRomanNumber($level + 1) . ", ". number_format($level + 2, 1) . "s" . TextFormat::DARK_BLUE . TextFormat::BOLD . "] *");
                            $damager->addCEPopup(TextFormat::BLUE . TextFormat::BOLD . "* ADRENALINE [" . TextFormat::RESET . TextFormat::GRAY . $player->getName() . TextFormat::DARK_BLUE . TextFormat::BOLD . "] *");
                            $player->getCESession()->setLastAdrenaline();
                        }
                    }
                }
            }
            return;
        };
    }
}