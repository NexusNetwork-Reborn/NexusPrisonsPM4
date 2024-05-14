<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class DominateEnchantment extends Enchantment {

    /**
     * DominateEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::DOMINATE, "Dominate", self::ELITE, "Weakens your enemy, making them deal less damage temporarily.", self::DAMAGE, self::SLOT_SWORD, 5);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getCESession()->isDominated()) {
                return;
            }
            $random = mt_rand(1, 300);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $entity->getCESession()->setDominated(true);
                $entity->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* DOMINATED [" . TextFormat::RESET . TextFormat::GRAY . number_format($level * 3, 1) . "s" . TextFormat::GOLD . TextFormat::BOLD . "] *");
                $damager->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* DOMINATED [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . ", ". number_format($level * 3, 1) . "s" . TextFormat::GOLD . TextFormat::BOLD . "] *");
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
                            $this->player->getCESession()->setDominated(false);
                            $this->player->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* DOMINATED LIFTED *");
                        }
                    }
                }, $level * 60);
            }
        };
    }
}