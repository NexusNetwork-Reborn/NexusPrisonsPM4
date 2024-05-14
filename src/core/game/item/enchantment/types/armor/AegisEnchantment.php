<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class AegisEnchantment extends Enchantment {

    /**
     * AegisEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::AEGIS, "Aegis", self::LEGENDARY, "Reduces enemy player offensive proc rates.", self::DAMAGE_BY, self::SLOT_ARMOR, 5);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($damager->getCESession()->hasAegis()) {
                return;
            }
            $random = mt_rand(1, 500);
            $chance = $level * $entity->getCESession()->getArmorLuckModifier();
            if($chance >= $random) {
                $damager->getCESession()->setAegis(1 - ($level * 0.025));
                $seconds = $level;
                $percent = $level * 2.5;
                $entity->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* AEGIS [" . TextFormat::RESET . TextFormat::GRAY . $damager->getName() . ", -$percent%%%, " . number_format($seconds, 1) . "s" . TextFormat::GOLD . TextFormat::BOLD . "] *");
                $damager->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* AEGIS [" . TextFormat::RESET . TextFormat::GRAY . "-$percent%%%, " . number_format($seconds, 1) . "s" . TextFormat::GOLD . TextFormat::BOLD . "] *");
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($damager) extends Task {

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
                            $this->player->getCESession()->setAegis(1.0);
                            $this->player->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* AEGIS LIFTED *");
                        }
                    }
                }, $seconds * 20);
            }
        };
    }
}