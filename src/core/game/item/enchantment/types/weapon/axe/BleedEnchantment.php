<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Task;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\BlockBreakParticle;

class BleedEnchantment extends Enchantment {

    /**
     * BleedEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::BLEED, "Bleed", self::ULTIMATE, "Chance of inflict bleed damage on your opponent.", self::DAMAGE, self::SLOT_AXE, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getCESession()->isBleeding()) {
                return;
            }
            $random = mt_rand(1, 175);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $bleed = $entity;
                $deflect = $entity->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::DEFLECT));
                if($deflect > 0) {
                    if($deflect >= mt_rand(1, 24)) {
                        $bleed = $damager;
                        $damager = $entity;
                        $entity->addCEPopup(TextFormat::YELLOW . TextFormat::BOLD . "** DEFLECTED [" . TextFormat::RESET . TextFormat::GRAY . $damager->getName() . ", Bleed " . EnchantmentManager::getRomanNumber($level) . TextFormat::YELLOW . TextFormat::BOLD . "] **");
                        $damager->addCEPopup(TextFormat::YELLOW . TextFormat::BOLD . "** DEFLECTED [" . TextFormat::RESET . TextFormat::GRAY . "Bleed " . EnchantmentManager::getRomanNumber($level) . TextFormat::YELLOW . TextFormat::BOLD . "] **");
                    }
                }
                $bleed->getCESession()->setBleeding(true);
                $dw = $damager->getInventory()->getItemInHand()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::DEEP_WOUNDS));
                Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new class($bleed, $damager, $level, $dw) extends Task {

                    /** @var NexusPlayer */
                    private $player;

                    /** @var NexusPlayer */
                    private $damager;

                    /** @var int */
                    private $level;

                    /** @var int */
                    private $dw;

                    /** @var int */
                    private $runs = 0;

                    /**
                     *  constructor.
                     *
                     * @param NexusPlayer $player
                     * @param NexusPlayer $damager
                     * @param int $level
                     * @param int $dw
                     */
                    public function __construct(NexusPlayer $player, NexusPlayer $damager, int $level, int $dw) {
                        $this->player = $player;
                        $this->damager = $damager;
                        $this->level = $level;
                        $this->dw = $dw;
                    }

                    /**
                     * @param int $currentTick
                     */
                    public function onRun(): void {
                        if(++$this->runs > (4 + $this->dw)) {
                            if($this->player->isOnline()) {
                                $this->player->getCESession()->setBleeding(false);
                            }
                            $this->cancel();
                            return;
                        }
                        if($this->player->isOnline() === false) {
                            $this->cancel();
                            return;
                        }
                        $level = $this->player->getWorld();
                        if($level === null) {
                            return;
                        }
                        $level->addParticle($this->player->getPosition()->add(0, 1, 0), new BlockBreakParticle(VanillaBlocks::REDSTONE()), [
                            $this->player,
                            $this->damager
                        ]);
                        $d = (($this->level * 3) + $this->player->getHealth()) * 0.05;
                        $tb = $this->player->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD));
                        if($tb > 0) {
                            $d *= (1 - ($tb * 0.015));
                        }
                        $this->player->attack(new EntityDamageEvent($this->player, EntityDamageEvent::CAUSE_MAGIC, $d));
                    }
                }, 20);
                $seconds = 4 + $dw;
                $bleed->addCEPopup(TextFormat::YELLOW . TextFormat::BOLD . "* BLEED [" . TextFormat::RESET . TextFormat::GRAY . number_format($seconds). TextFormat::YELLOW . TextFormat::BOLD . "] *");
                $damager->addCEPopup(TextFormat::YELLOW . TextFormat::BOLD . "* BLEED [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . ", ". number_format($seconds). TextFormat::YELLOW . TextFormat::BOLD . "] *");
            }
        };
    }
}