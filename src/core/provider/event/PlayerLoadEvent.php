<?php
declare(strict_types=1);

namespace core\provider\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerLoadEvent extends PlayerEvent {

    /**
     * PlayerLoadEvent constructor.
     *
     * @param Player $player
     */
    public function __construct(Player $player) {
        $this->player = $player;
    }
}