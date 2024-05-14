<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\mask\Mask;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Armor;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class TrapEnchantment extends Enchantment {

    /**
     * TrapEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::TRAP, "Trap", self::LEGENDARY, "Chance to freeze target player.", self::DAMAGE, self::SLOT_SWORD, 4, self::SLOT_AXE);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getCESession()->isTrapped()) {
                return;
            }
            $random = mt_rand(1, 250);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $do = true;

                if(SetUtils::isWearingFullSet($entity, "yeti") && $entity->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "") === "yeti") {
                    if(mt_rand(1, 100) <= 25) {
                        $do = false;
                    }
                }

                if(!$do) return;

                $helm = $damager->getArmorInventory()->getHelmet();
                $jailor = false;
                if ($helm instanceof Armor && $helm->hasMask(Mask::JAILOR)) {
                    $jailor = true;
                }

                $trapped = $entity;
                $houdini = $trapped->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::HOUDINI));
                if($jailor) {
                    $houdini = 0;
                }
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
                if($jailor) {
                    $deduct = 0;
                }
                $ticks = ($level + 4) * 20;
                if($deduct > 0) {
                    $ticks -= $deduct * 5;
                    if($ticks <= 0) {
                        $ticks = 40;
                    }
                }
                $trapped->getCESession()->setTrapped(true);
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
                            $this->player->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* TRAP LIFTED *");
                        }
                    }
                }, $ticks);
                $seconds = $ticks * 0.05;
                $trapped->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* TRAPPED [" . TextFormat::RESET . TextFormat::GRAY . number_format($seconds). TextFormat::GOLD . TextFormat::BOLD . "] *");
                if($trapped->getUniqueId()->toString() !== $damager->getUniqueId()->toString()) {
                    $entity->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* TRAPPED [" . TextFormat::RESET . TextFormat::GRAY . $trapped->getName() . ", ". number_format($seconds). TextFormat::GOLD . TextFormat::BOLD . "] *");
                }
                else {
                    $damager->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* TRAPPED [" . TextFormat::RESET . TextFormat::GRAY . $trapped->getName() . ", ". number_format($seconds). TextFormat::GOLD . TextFormat::BOLD . "] *");
                }
            }
        };
    }
}