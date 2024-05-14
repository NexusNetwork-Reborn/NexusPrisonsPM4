<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class TitanTrapEnchantment extends Enchantment {

    /**
     * TitanTrapEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::TITAN_TRAP, "Titan Trap", self::EXECUTIVE, "Chance to freeze target players and silence attacks (Requires Trap 4)", self::DAMAGE, self::SLOT_SWORD, 3, self::SLOT_AXE, self::TRAP);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getCESession()->isTrapped() and $entity->getCESession()->isSilenced()) {
                return;
            }
            $random = mt_rand(1, 150);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $do = true;

                if(SetUtils::isWearingFullSet($entity, "yeti") && $entity->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "") === "yeti") {
                    if(mt_rand(1, 100) <= 25) {
                        $do = false;
                    }
                }

                if(!$do) return;

                $trapped = $entity;
                $houdini = $trapped->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::HOUDINI));
                if($houdini > 0) {
                    if($houdini >= mt_rand(1, 5)) {
                        if($entity->payEnergy($houdini * 500000)) {
                            if(mt_rand(1, 2) === 1) {
                                $entity->addCEPopup(TextFormat::AQUA . TextFormat::BOLD . "** HOUDINI [" . TextFormat::RESET . TextFormat::GRAY . "NEGATED, -" . number_format($houdini * 500000) . " Energy" . TextFormat::AQUA . TextFormat::BOLD . "] **");
                            }
                            else {
                                $trapped = $damager;
                                $entity->addCEPopup(TextFormat::AQUA . TextFormat::BOLD . "** HOUDINI [" . TextFormat::RESET . TextFormat::GRAY . $damager->getName() . ", REFLECTED, -" . number_format($houdini * 500000) . " Energy" . TextFormat::AQUA . TextFormat::BOLD . "] **");
                                $damager->addCEPopup(TextFormat::AQUA . TextFormat::BOLD . "** HOUDINI [" . TextFormat::RESET . TextFormat::GRAY . "REFLECTED, -" . number_format($houdini * 500000) . " Energy" . TextFormat::AQUA . TextFormat::BOLD . "] **");
                            }
                        }
                    }
                }
                $houdini = $trapped->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::HOUDINI));
                $deduct = $trapped->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(self::ESCAPIST)) + $houdini;
                $ticks = ($level + 6) * 20;
                if($deduct > 0) {
                    $ticks -= $deduct * 5;
                    if($ticks <= 0) {
                        $ticks = 40;
                    }
                }
                $trapped->getCESession()->setTrapped(true);
                $trapped->getCESession()->setSilenced(true);
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($trapped) extends Task {

                    /** @var NexusPlayer */
                    private $player;

                    /**
                     *  constructor.
                     *
                     * @param NexusPlayer $player
                     */
                    public function __construct(NexusPlayer $player) {
                        $this->player = $player;
                    }

                    /**
                     * @param int $currentTick
                     */
                    public function onRun(): void {
                        if($this->player->isOnline() === false) {
                            return;
                        }
                        if($this->player->getCESession()->isTrapped()) {
                            $this->player->getCESession()->setTrapped(false);
                        }
                        if($this->player->getCESession()->isSilenced()) {
                            $this->player->getCESession()->setSilenced(false);
                        }
                        $this->player->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* TITAN TRAP LIFTED *");
                    }
                }, $ticks);
                $seconds = $ticks * 0.05;
                $trapped->addCEPopup(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "** TITAN TRAPPED [" . TextFormat::RESET . TextFormat::GRAY . number_format($seconds). TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "] **");
                if($trapped->getUniqueId()->toString() !== $damager->getUniqueId()->toString()) {
                    $entity->addCEPopup(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "** TITAN TRAPPED [" . TextFormat::RESET . TextFormat::GRAY . $trapped->getName() . ", ". number_format($seconds). TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "] **");
                }
                else {
                    $damager->addCEPopup(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "** TITAN TRAPPED [" . TextFormat::RESET . TextFormat::GRAY . $trapped->getName() . ", ". number_format($seconds). TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "] **");
                }
            }
        };
    }
}