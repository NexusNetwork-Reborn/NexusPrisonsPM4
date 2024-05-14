<?php

declare(strict_types=1);

namespace core\game\quest\types;

use core\game\quest\Quest;
use core\level\entity\types\Lightning;
use core\level\entity\types\PummelBlock;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDeathEvent;

class KillQuest extends Quest {

    /**
     * MineQuest constructor.
     *
     * @param string $name
     * @param string $description
     * @param string $rarity
     * @param int $targetValue
     */
    public function __construct(string $name, string $description, string $rarity, int $targetValue) {
        $callable = function(PlayerDeathEvent $event) {
            $cause = $event->getPlayer()->getLastDamageCause();
            if($cause instanceof EntityDamageByEntityEvent) {
                $killer = $cause->getDamager();
                if($killer instanceof Lightning) {
                    $killer = $killer->getOwningEntity();
                }
                if($killer instanceof PummelBlock) {
                    $killer = $killer->getOwningEntity();
                }
                if($killer instanceof NexusPlayer) {
                    $progress = $killer->getDataSession()->getQuestProgress($this->getName());
                    if($progress < $this->targetValue) {
                        $progress = min($this->targetValue, ++$progress);
                        $killer->getDataSession()->setQuestProgress($this->getName(), $progress);
                        if($progress >= $this->targetValue) {
                            $this->celebrate($killer);
                        }
                    }
                }
            }
        };
        parent::__construct($name, $description, $rarity, self::KILL, $targetValue, $callable);
    }
}