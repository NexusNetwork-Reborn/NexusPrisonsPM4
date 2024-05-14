<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class HexCurseEnchantment extends Enchantment {

    /**
     * HexCurseEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::HEX_CURSE, "Hex Curse", self::EXECUTIVE, "Chance to cause your enemies max HP to be temporarily lowered and deal MASSIVE damage to any enemy who kills you (Requires Curse 3)", self::DEATH, self::SLOT_ARMOR, 3, self::SLOT_NONE, self::CURSE);
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
                    $damager->setMaxHealth(20);
                    $ev = new EntityDamageByEntityEvent($player, $damager, EntityDamageEvent::CAUSE_CUSTOM, $damager->getHealth() * 1.5, [], 0);
                    $damager->attack($ev);
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
                        $player->sendMessage(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . " ** HEX CURSE [" . TextFormat::RESET . TextFormat::RED . $damager->getName() . ", " . number_format(($level * 30) + 30, 1) . "s" . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "] **");
                    }
                    $damager->getCESession()->setHexCursed(true);
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
                                $this->player->getCESession()->setHexCursed(false);
                                $this->player->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* HEX CURE LIFTED *");
                            }
                        }
                    }, ($level * 600) + 600);
                }
            }
        };
    }
}