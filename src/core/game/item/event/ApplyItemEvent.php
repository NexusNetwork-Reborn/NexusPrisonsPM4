<?php
declare(strict_types=1);

namespace core\game\item\event;

use core\game\item\types\CustomItem;
use pocketmine\event\player\PlayerEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class ApplyItemEvent extends PlayerEvent {

    /** @var CustomItem */
    private $item;

    /**
     * ApplyItemEvent constructor.
     *
     * @param Player $player
     * @param CustomItem $item
     */
    public function __construct(Player $player, CustomItem $item) {
        $this->player = $player;
        $this->item = $item;
    }

    /**
     * @return CustomItem
     */
    public function getItem(): CustomItem {
        return $this->item;
    }
}