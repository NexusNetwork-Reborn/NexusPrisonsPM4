<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class SilenceEnchantment extends Enchantment {

    /**
     * SilenceEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SILENCE, "Silence", self::GODLY, "Chance to temporarily reduce your enemies defensive enchant proc rates by 50%.", self::DAMAGE, self::SLOT_SWORD, 5, self::SLOT_AXE);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getCESession()->isSilenced()) {
                return;
            }
            $random = mt_rand(1, 500);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            $solitude = $damager->getInventory()->getItemInHand()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::SOLITUDE));
            if($solitude > 0) {
                $chance += $solitude * 5;
            }
            if($chance >= $random) {
                $entity->getCESession()->setSilenced(true);
                $seconds = $level * 3;
                $entity->addCEPopup(TextFormat::RED . TextFormat::BOLD . "* SILENCE [" . TextFormat::RESET . TextFormat::GRAY . number_format($seconds, 1) . "s" . TextFormat::GOLD . TextFormat::BOLD . "] *");
                $damager->addCEPopup(TextFormat::RED . TextFormat::BOLD . "* SILENCE [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . ", " . number_format($seconds, 1) . "s" . TextFormat::RED . TextFormat::BOLD . "] *");
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
                            $this->player->getCESession()->setSilenced(false);
                            $this->player->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* SILENCE LIFTED *");
                        }
                    }
                }, $seconds * 20);
            }
        };
    }
}