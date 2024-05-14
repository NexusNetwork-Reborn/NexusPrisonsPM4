<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\mask\Mask;
use core\game\item\types\vanilla\Armor;
use core\level\entity\types\Lightning;
use core\player\NexusPlayer;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;

class LightningEnchantment extends Enchantment {

    /**
     * LightningEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::LIGHTNING, "Lightning", self::LEGENDARY, "Chance to strike your opponent with lightning.", self::DAMAGE, self::SLOT_SWORD, 3, self::SLOT_AXE);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getWorld()->getFolderName() === "boss" || $entity->getWorld()->getFolderName() === "executive") {
                return;
            }
            $helm = $entity->getArmorInventory()->getHelmet();
            if ($helm instanceof Armor && $helm->hasMask(Mask::FIREFLY)) {
                return;
            }
            $random = mt_rand(1, 250);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $struck = $entity;
                $notStruck = $damager;
                $deflect = $struck->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::DEFLECT));
                if($deflect > 0) {
                    if($deflect >= mt_rand(1, 24)) {
                        $struck = $damager;
                        $notStruck = $entity;
                        $entity->addCEPopup(TextFormat::YELLOW . TextFormat::BOLD . "** DEFLECTED [" . TextFormat::RESET . TextFormat::GRAY . $damager->getName() . ", Lightning " . EnchantmentManager::getRomanNumber($level) . TextFormat::YELLOW . TextFormat::BOLD . "] **");
                        $damager->addCEPopup(TextFormat::YELLOW . TextFormat::BOLD . "** DEFLECTED [" . TextFormat::RESET . TextFormat::GRAY . "Lightning " . EnchantmentManager::getRomanNumber($level) . TextFormat::YELLOW . TextFormat::BOLD . "] **");
                    }
                }
                $pos = $struck->getPosition();
                $lightning = new Lightning(new Location($pos->x, $pos->y, $pos->z, $pos->getWorld(), 0, 0));
                $lightning->spawnToAll();
                $lightning->setOwningEntity($notStruck);
                $lightning->setTargetEntity($struck);
                $lightning->setStrikes($level + 2);
                $lightning->setDamageDone(4);
            }
        };
    }
}