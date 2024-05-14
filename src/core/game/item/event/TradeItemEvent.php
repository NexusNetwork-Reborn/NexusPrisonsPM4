<?php
declare(strict_types=1);

namespace core\game\item\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class TradeItemEvent extends PlayerEvent {

    /** @var Item[] */
    private $items;

    /**
     * TradeItemEvent constructor.
     *
     * @param Player $player
     * @param array $items
     */
    public function __construct(Player $player, array $items) {
        $this->player = $player;
        $this->items = $items;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }
}