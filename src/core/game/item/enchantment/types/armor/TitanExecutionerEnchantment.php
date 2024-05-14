<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\enchantment\ItemFlags;

class TitanExecutionerEnchantment extends Enchantment
{

    public function __construct()
    {
        parent::__construct(self::TITAN_EXECUTIONER,
            "Titan Executioner",
            self::GODLY,
            "Deal increased damage to enemies with Titan or Leviathan Blood.",
            self::DAMAGE,
            ItemFlags::ARMOR,
            5
        );

        $this->callable = function (EntityDamageByEntityEvent $event, int $level, float &$damage) : void {
            $damager = $event->getDamager();
            $entity = $event->getEntity();

            if(!$damager instanceof NexusPlayer || !$entity instanceof NexusPlayer) return;

            $do = false;

            foreach ($entity->getArmorInventory()->getContents() as $armor) {
                if($armor->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::LEVIATHAN_BLOOD)) || $armor->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD))) $do = true;
            }

            if($do) {
                $damage *= (1 + ($level * 0.025));
            }
        };
    }
}