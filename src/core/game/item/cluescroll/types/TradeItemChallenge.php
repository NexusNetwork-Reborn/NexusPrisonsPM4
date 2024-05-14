<?php

declare(strict_types=1);

namespace core\game\item\cluescroll\types;

use core\game\gamble\event\CoinFlipLoseEvent;
use core\game\item\cluescroll\Challenge;
use core\game\item\event\TradeItemEvent;
use core\game\item\types\custom\ClueScroll;
use core\game\item\types\Rarity;
use core\player\NexusPlayer;
use pocketmine\item\Item;

class TradeItemChallenge extends Challenge {

    /**
     * TinkerEquipmentChallenge constructor.
     *
     * @param int $id
     * @param string $rarity
     */
    public function __construct(int $id, string $rarity) {
        $callable = function(TradeItemEvent $event, Item $scroll) {
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
                $items = count($event->getItems());
                if($items >= 1) {
                    $this->celebrate($player, $scroll, $scrollInstance);
                }
            }
        };
        $description = "Trade a player at least 1 item";
        parent::__construct($id, $description, $rarity, self::TRADE, $callable);
    }
}