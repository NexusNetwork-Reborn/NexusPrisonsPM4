<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\game\item\sets\utils\SetUtils;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\TextFormat;

class TitanBloodEnchantment extends Enchantment {

    /**
     * TitanBloodEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::TITAN_BLOOD, "Titan Blood", self::LEGENDARY, "Reduces ALL incoming damage and tick damage.", self::DAMAGE_BY_ALL, self::SLOT_ARMOR, 4);
        $this->callable = function(EntityDamageEvent $event, int $level, float &$damage) {
            if($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if($damager instanceof NexusPlayer && SetUtils::isWearingFullSet($damager, "plaguedoctor") && mt_rand(1, 100) <= 50) {
                    $damager->addCEPopup(TextFormat::BOLD . TextFormat::GRAY . "* PLAGUE DOCTOR [" . TextFormat::RESET . TextFormat::RED . "Blocked Titan Blood" . TextFormat::BOLD . TextFormat::GRAY . "] *");
                    return;
                }
            }
            $damage *= (1 - ($level * 0.015));
            return;
        };
    }
}