<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class CurseEnchantment extends Enchantment {

    /**
     * CurseEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::CURSE, "Curse", self::ELITE, "Curses your killer upon death.", self::DEATH, self::SLOT_ARMOR, 3);
        $this->callable = function(PlayerDeathEvent $event, int $level) {
            $level = min(3, $level);
            $player = $event->getPlayer();
            if($player instanceof NexusPlayer) {
                $cause = $player->getLastDamageCause();
                if($cause instanceof EntityDamageByEntityEvent) {
                    $damager = $cause->getDamager();
                    if(!$damager instanceof NexusPlayer) {
                        return;
                    }
                    if($damager->getCESession()->isHexCursed()) {
                        return;
                    }
                    $p = [];
                    $bb = $damager->getBoundingBox()->expandedCopy(20, 20, 20);
                    foreach($damager->getWorld()->getNearbyEntities($bb) as $e) {
                        if($e instanceof NexusPlayer) {
                            $p[] = $e;
                        }
                    }
                    if(empty($p)) {
                        return;
                    }
                    foreach($p as $player) {
                        $player->sendMessage(TextFormat::BLUE . TextFormat::BOLD . " * CURSE [" . TextFormat::RESET . TextFormat::RED . $damager->getName() . " " . number_format($level * 20, 1) . "s" . TextFormat::BLUE . TextFormat::BOLD . "] *");
                    }
                    $damager->getCESession()->setCursed(true);
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
                                $this->player->getCESession()->setCursed(false);
                                $this->player->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* CURSE LIFTED *");
                            }
                        }
                    }, $level * 400);
                }
            }
        };
    }
}