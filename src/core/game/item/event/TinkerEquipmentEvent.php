<?php
declare(strict_types=1);

namespace core\game\item\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class TinkerEquipmentEvent extends PlayerEvent {

    /** @var Item */
    private $item;

    /**
     * TinkerEquipmentEvent constructor.
     *
     * @param Player $player
     * @param Item $item
     */
    public function __construct(Player $player, Item $item) {
        $this->player = $player;
        $this->item = $item;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }
}