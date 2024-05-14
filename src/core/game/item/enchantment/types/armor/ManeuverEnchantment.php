<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;

class ManeuverEnchantment extends Enchantment {

    /**
     * ManeuverEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::MANEUVER, "Maneuver", self::SIMPLE, "Chance of dodging an attack.", self::DAMAGE_BY, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            $random = mt_rand(1, 500);
            $chance = min(50, $level * $entity->getCESession()->getArmorLuckModifier());
            if($chance >= $random) {
                $event->cancel();
                $entity->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* MANEUVER [" . TextFormat::RESET . TextFormat::RED . $entity->getName() . TextFormat::GRAY . TextFormat::BOLD . "] *");
                $damager = $event->getDamager();
                if($damager instanceof NexusPlayer) {
                    $damager->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* MANEUVER [" . TextFormat::RESET . TextFormat::RED . $entity->getName() . TextFormat::GRAY . TextFormat::BOLD . "] *");
                }
            }
        };
    }
}