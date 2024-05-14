<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class BloodlustEnchantment extends Enchantment {

    /**
     * BloodlustEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::BLOODLUST, "Bloodlust", self::LEGENDARY, "If your victim is bleeding, your blows deal extra damage", self::DAMAGE, self::SLOT_AXE, 6);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            if($entity->getCESession()->isBleeding()) {
                $damage *= (1 + ($level * 0.05));
            }
        };
    }
}