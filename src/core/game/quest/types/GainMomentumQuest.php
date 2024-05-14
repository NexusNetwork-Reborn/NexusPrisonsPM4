<?php

declare(strict_types=1);

namespace core\game\quest\types;

use core\game\quest\Quest;
use core\level\block\Meteorite;
use core\player\NexusPlayer;
use pocketmine\event\block\BlockBreakEvent;

class GainMomentumQuest extends Quest {

    /**
     * MineQuest constructor.
     *
     * @param string $name
     * @param string $description
     * @param string $rarity
     * @param int $targetValue
     */
    public function __construct(string $name, string $description, string $rarity, int $targetValue) {
        $callable = function(BlockBreakEvent $event): void {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($player->getCESession()->getMomentumSpeed() >= 25) {
                $progress = $player->getDataSession()->getQuestProgress($this->getName());
                if($progress < $this->targetValue) {
                    $progress = min($this->targetValue, ++$progress);
                    $player->getDataSession()->setQuestProgress($this->getName(), $progress);
                    if($progress >= $this->targetValue) {
                        $this->celebrate($player);
                    }
                }
            }
        };
        parent::__construct($name, $description, $rarity, self::MOMENTUM, $targetValue, $callable);
    }
}