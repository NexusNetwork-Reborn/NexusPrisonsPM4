<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use core\game\item\sets\type\PlagueDoctorSet;
use core\game\item\sets\utils\SetUtils;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class PlaguedSmiteEnchantment extends Enchantment
{

    public function __construct()
    {
        parent::__construct(
            self::PLAGUED_SMITE,
            "Plagued Smite",
            self::ENERGY,
            "Deal extra strong attacks which inhibit a target's healing (costs 50k energy per swing)",
            self::DAMAGE,
            ItemFlags::AXE,
            3
        );

        $this->callable = function (EntityDamageByEntityEvent $event, int $level, float &$damage) : void  {
            $damager = $event->getDamager();
            $victim = $event->getEntity();

            if(!$damager instanceof NexusPlayer && !$victim instanceof NexusPlayer || isset(PlagueDoctorSet::$reduceHealing[$victim->getUniqueId()->toString()])) return;

            if(!$damager->payEnergy(50000)) return;

            if(SetUtils::isWearingFullSet($victim, "underling") && (4 - $level) * $victim->getCESession()->getArmorLuckModifier() > mt_rand(1, 500)) {
                $victim->addCEPopup(TextFormat::BOLD . TextFormat::GRAY . "* UNDERLING [" . TextFormat::RED . "Blocked Plague Smite" . TextFormat::BOLD . TextFormat::GRAY . "] *");
            } else {
                PlagueDoctorSet::$reduceHealing[$victim->getUniqueId()->toString()] = $victim->getUniqueId()->toString();
                $id = $victim->getUniqueId()->toString();

                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use($id) : void  {
                    if(isset(PlagueDoctorSet::$reduceHealing[$id])) unset(PlagueDoctorSet::$reduceHealing[$id]);
                }), 20 * 5);

                $victim->addCEPopup(TextFormat::BOLD . TextFormat::GRAY . "* PLAGUED SMITE [" . TextFormat::RED . "Inflicted By " . $damager->getName() . TextFormat::BOLD . TextFormat::GRAY . "] *");
                $damager->addCEPopup(TextFormat::BOLD . TextFormat::GRAY . "* PLAGUED SMITE [" . TextFormat::RED . "Inflicted On " . $victim->getName() . TextFormat::BOLD . TextFormat::GRAY . "] *");
            }


            $damage *= 1.25;
        };
    }
}