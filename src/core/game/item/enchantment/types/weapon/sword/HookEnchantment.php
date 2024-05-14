<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;

class HookEnchantment extends Enchantment {

    /**
     * HookEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::HOOK, "Hook", self::ULTIMATE, "Chance to cause your target to pull towards you.", self::DAMAGE, self::SLOT_SWORD, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            $random = mt_rand(1, 175);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $entity->setMotion($damager->getDirectionVector()->multiply(-($damager->getPosition()->distance($entity->getPosition()) / 6))->add(0, 0.3, 0));
                $entity->addCEPopup(TextFormat::YELLOW . TextFormat::BOLD . "* HOOKED *");
                $damager->addCEPopup(TextFormat::YELLOW . TextFormat::BOLD . "* HOOKED [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . TextFormat::GOLD . TextFormat::BOLD . "] *");
            }
        };
    }
}