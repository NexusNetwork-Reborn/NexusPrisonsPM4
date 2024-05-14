<?php

declare(strict_types=1);

namespace core\game\item\cluescroll\types;

use core\game\item\cluescroll\Challenge;
use core\game\item\event\OpenItemEvent;
use core\game\item\types\custom\ClueScroll;
use core\game\item\types\custom\Contraband;
use core\game\item\types\Rarity;
use core\player\NexusPlayer;
use pocketmine\item\Item;

class OpenContrabandChallenge extends Challenge {

    /**
     * OpenShardChallenge constructor.
     *
     * @param int $id
     * @param string $rarity
     */
    public function __construct(int $id, string $rarity) {
        $callable = function(OpenItemEvent $event, Item $scroll) {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $scrollInstance = ClueScroll::fromItem($scroll);
            if($scrollInstance === null) {
                return;
            }
            $challenge = $scrollInstance->getCurrentChallenge();
            if($challenge === $this->getId()) {
                $item = $event->getItem();
                if($item instanceof Contraband) {
                    if(Rarity::RARITY_TO_ENCHANTMENT_RARITY_MAP[$item->getRarity()] >= Rarity::RARITY_TO_ENCHANTMENT_RARITY_MAP[$this->getRarity()]) {
                        $this->celebrate($player, $scroll, $scrollInstance);
                    }
                }
            }
        };
        $description = "Open a " . $rarity . "+ Contraband";
        parent::__construct($id, $description, $rarity, self::OPEN_ITEM, $callable);
    }
}