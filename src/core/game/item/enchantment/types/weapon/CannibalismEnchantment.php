<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;

class CannibalismEnchantment extends Enchantment {

    /**
     * CannibalismEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::CANNIBALISM, "Cannibalism", self::UNCOMMON, "Chance to increase your saturation and hunger level.", self::DAMAGE, self::SLOT_SWORD, 4, self::SLOT_AXE);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getDamager();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            if(($entity->getHungerManager()->getMaxFood() - $entity->getHungerManager()->getFood()) < 4) {
                return;
            }
            $random = mt_rand(1, 40);
            $level *= $entity->getCESession()->getItemLuckModifier();
            if($level >= $random) {
                $pk = new LevelSoundEventPacket();
                $pk->position = $entity->getPosition();
                $pk->sound = LevelSoundEvent::BURP;
                $entity->getNetworkSession()->sendDataPacket($pk);
                $newFood = min($entity->getHungerManager()->getFood() + (0.5 * $level), $entity->getHungerManager()->getMaxFood());
                $entity->getHungerManager()->setFood($newFood);
            }
        };
    }
}