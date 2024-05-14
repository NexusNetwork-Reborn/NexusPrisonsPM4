<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\bow;

use core\game\item\enchantment\Enchantment;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class TrinketBlockEnchantment extends Enchantment {

    /**
     * TrinketBlockEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::TRINKET_BLOCK, "Trinket Block", self::ENERGY, "Chance to stop your target from using trinkets temporarily. (100k energy per shot)", self::DAMAGE, self::SLOT_BOW, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($event->getCause() !== EntityDamageByEntityEvent::CAUSE_PROJECTILE) {
                return;
            }
            if($entity->getCESession()->isTrinketBlocked()) {
                return;
            }
            $price = 100000;
            if(!$damager->payEnergy($price)) {
                return;
            }
            $random = mt_rand(1, 20);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $ticks = ($level * 10) * 20;
                $entity->getCESession()->setTrinketBlocked(true);
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entity) extends Task {

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
                        if($this->player->getCESession()->isTrinketBlocked()) {
                            $this->player->getCESession()->setTrinketBlocked(false);
                            $this->player->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* TRINKET BLOCK LIFTED *");
                        }
                    }
                }, $ticks);
                $seconds = $ticks * 0.05;
                $entity->addCEPopup(TextFormat::AQUA . TextFormat::BOLD . "** TRINKET BLOCKED [" . TextFormat::RESET . TextFormat::GRAY . number_format($seconds, 1) . "s" . TextFormat::AQUA . TextFormat::BOLD . "] **");
                $damager->addCEPopup(TextFormat::AQUA . TextFormat::BOLD . "** TRINKET BLOCKED [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . ", ". number_format($seconds, 1) . "s" . TextFormat::AQUA . TextFormat::BOLD . "] **");
                return;
            }
        };
    }
}