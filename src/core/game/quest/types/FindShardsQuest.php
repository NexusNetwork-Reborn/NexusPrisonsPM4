<?php

declare(strict_types=1);

namespace core\game\quest\types;

use core\game\item\event\ShardFoundEvent;
use core\game\quest\Quest;
use core\player\NexusPlayer;

class FindShardsQuest extends Quest {

    /**
     * FindShardsQuest constructor.
     *
     * @param string $name
     * @param string $description
     * @param string $rarity
     * @param int $targetValue
     */
    public function __construct(string $name, string $description, string $rarity, int $targetValue) {
        $callable = function(ShardFoundEvent $event) {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $progress = $player->getDataSession()->getQuestProgress($this->getName());
            if($progress < $this->targetValue) {
                $progress = min($this->targetValue, $progress + $event->getAmount());
                $player->getDataSession()->setQuestProgress($this->getName(), $progress);
                if($progress >= $this->targetValue) {
                    $this->celebrate($player);
                }
            }
        };
        parent::__construct($name, $description, $rarity, self::FIND_SHARDS, $targetValue, $callable);
    }
}