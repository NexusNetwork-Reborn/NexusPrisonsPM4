<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class ScorchEnchantment extends Enchantment {

    /**
     * ScorchEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SCORCH, "Scorch", self::UNCOMMON, "Chance to breathe fire at your opponent.", self::DAMAGE, self::SLOT_SWORD, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->isOnFire()) {
                return;
            }
            $random = mt_rand(1, 175);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $scorch = $entity;
                $deflect = $entity->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::DEFLECT));
                if($deflect > 0) {
                    if($deflect >= mt_rand(1, 24)) {
                        $scorch = $damager;
                    }
                }
                $scorch->setOnFire($level * 4);
            }
        };
    }
}