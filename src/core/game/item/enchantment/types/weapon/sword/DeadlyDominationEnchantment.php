<?php

declare(strict_types=1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class DeadlyDominationEnchantment extends Enchantment
{

    public function __construct()
    {
        parent::__construct(self::DEADLY_DOMINATION,
            "Deadly Domination",
            self::EXECUTIVE,
            "Increased chance to weaken enemies and deal extra damage (Requires Dominate 5)",
            self::DAMAGE,
            ItemFlags::SWORD,
            4,
            ItemFlags::SWORD,
            self::DOMINATE
        );

        $this->callable = function (EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getCESession()->isDominated()) {
                return;
            }
            $random = mt_rand(1, 300);
            $chance = ($level * $damager->getCESession()->getItemLuckModifier()) * 2.5;
            if($chance >= $random) {
                $entity->getCESession()->setDominated(true);
                $entity->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* DEADLY DOMINATION [" . TextFormat::RESET . TextFormat::GRAY . number_format($level * 3, 1) . "s" . TextFormat::GOLD . TextFormat::BOLD . "] *");
                $damager->addCEPopup(TextFormat::GOLD . TextFormat::BOLD . "* DEADLY DOMINATION [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . ", ". number_format($level * 3, 1) . "s" . TextFormat::GOLD . TextFormat::BOLD . "] *");
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
                            $this->player->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* DEADLY DOMINATION LIFTED *");
                        }
                    }
                }, $level * 60);

                $damage *= ($level * 0.035);
            }
        };
    }
}