<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class InfernoEnchantment extends Enchantment {

    /**
     * InfernoEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::INFERNO, "Inferno", self::UNCOMMON, "Chance to ignite your attackers.", self::DAMAGE_BY, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $player = $event->getEntity();
            $damager = $event->getDamager();
            if($player instanceof NexusPlayer) {
                $random = mt_rand(1, 300);
                $chance = $level * $player->getCESession()->getArmorLuckModifier();
                if($chance >= $random) {
                    if(!$damager->isOnFire()) {
                        $damager->setOnFire($level * 4);
                    }
                }
            }
            return;
        };
    }
}