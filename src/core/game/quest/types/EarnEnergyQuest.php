<?php

declare(strict_types=1);

namespace core\game\quest\types;

use core\game\item\event\EarnEnergyEvent;
use core\game\quest\Quest;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class EarnEnergyQuest extends Quest {

    /**
     * EarnEnergyQuest constructor.
     *
     * @param string $name
     * @param string $description
     * @param string $rarity
     * @param int $targetValue
     */
    public function __construct(string $name, string $description, string $rarity, int $targetValue) {
        $callable = function(EarnEnergyEvent $event): void {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $progress = $player->getDataSession()->getQuestProgress($this->getName());
            if($progress < $this->targetValue) {
                $progress = min($this->targetValue, ($progress + $event->getAmount()));
                $player->getDataSession()->setQuestProgress($this->getName(), $progress);
                if($progress >= $this->targetValue) {
                    $this->celebrate($player);
                }
            }
        };
        parent::__construct($name, $description, $rarity, self::EARN_ENERGY, $targetValue, $callable);
    }
}