<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\utils\TextFormat;

class EnlightedEnchantment extends Enchantment {

    /**
     * EnlightedEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ENLIGHTED, "Enlighted", self::LEGENDARY, "Chance to turn incoming damage into healing.", self::DAMAGE_BY_ALL, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            $random = mt_rand(1, 500);
            $chance = min(25, $level * $entity->getCESession()->getArmorLuckModifier());
            if($chance >= $random) {
                $heal = 1 + $event->getFinalDamage() * (0.1 * $level);
                $entity->heal(new EntityRegainHealthEvent($entity, $heal, EntityRegainHealthEvent::CAUSE_CUSTOM));
                $entity->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* ENLIGHTED [" . TextFormat::RESET . TextFormat::WHITE . number_format($heal, 1) . TextFormat::RED . TextFormat::BOLD . " HP" . TextFormat::GOLD . "] *");
            }
        };
    }
}