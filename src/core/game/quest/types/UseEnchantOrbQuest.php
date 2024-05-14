<?php

declare(strict_types=1);

namespace core\game\quest\types;

use core\game\quest\Quest;
use core\game\wormhole\event\EnchantmentOrbUseEvent;
use core\player\NexusPlayer;

class UseEnchantOrbQuest extends Quest {

    /**
     * UseEnchantOrbQuest constructor.
     *
     * @param string $name
     * @param string $description
     * @param string $rarity
     * @param int $targetValue
     */
    public function __construct(string $name, string $description, string $rarity, int $targetValue) {
        $callable = function(EnchantmentOrbUseEvent $event) {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $progress = $player->getDataSession()->getQuestProgress($this->getName());
            if($progress < $this->targetValue) {
                $progress = min($this->targetValue, ++$progress);
                $player->getDataSession()->setQuestProgress($this->getName(), $progress);
                if($progress >= $this->targetValue) {
                    $this->celebrate($player);
                }
            }
        };
        parent::__construct($name, $description, $rarity, self::USE_ENCHANT_ORB, $targetValue, $callable);
    }
}