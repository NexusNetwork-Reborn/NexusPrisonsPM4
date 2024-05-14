<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class WeaknessEnchantment extends Enchantment {

    /**
     * WeaknessEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::WEAKNESS, "Weakness", self::LEGENDARY, "Chance to mark a player to receive increased damage.", self::DAMAGE, self::SLOT_AXE, 5);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            $random = mt_rand(1, 300);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $entity->getCESession()->setWeakened(true);
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
                        if($this->player->isOnline()) {
                            $this->player->getCESession()->setWeakened(false);
                            $this->player->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* WEAKNESS LIFTED *");
                        }
                    }
                }, ($level + 1) * 20);
                $seconds = ($level + 1);
                $entity->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* WEAKNESS [" . TextFormat::RESET . TextFormat::GRAY . number_format($seconds). TextFormat::GOLD . TextFormat::BOLD . "] *");
                $damager->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* WEAKNESS [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . ", ". number_format($seconds). TextFormat::GOLD . TextFormat::BOLD . "] *");
            }
        };
    }
}