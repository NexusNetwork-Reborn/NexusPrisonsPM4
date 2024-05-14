<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\utils\TextFormat;

class BloodMagicEnchantment extends Enchantment {

    /**
     * BloodMagicEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::BLOOD_MAGIC, "Blood Magic", self::ULTIMATE, "Chance to take extra durability damage and heal yourself.", self::DAMAGE_BY, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            $random = mt_rand(1, 500);
            $chance = min(25, $level * $entity->getCESession()->getArmorLuckModifier());
            if($chance >= $random) {
                $d = mt_rand(1, $level);
                $entity->damageArmor($d);
                $heal = 1 + $event->getFinalDamage() * (0.1 * $level);
                $entity->heal(new EntityRegainHealthEvent($entity, $heal, EntityRegainHealthEvent::CAUSE_CUSTOM));
                $entity->addCEPopup(TextFormat::YELLOW . TextFormat::BOLD . "* BLOOD MAGIC [" . TextFormat::RESET . TextFormat::WHITE . number_format($heal, 1) . TextFormat::RED . TextFormat::BOLD . " HP" . TextFormat::YELLOW . "] *");
            }
        };
    }
}