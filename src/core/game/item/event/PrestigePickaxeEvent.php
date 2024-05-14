<?php
declare(strict_types=1);

namespace core\game\item\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PrestigePickaxeEvent extends PlayerEvent {

    /**
     * ShardFoundEvent constructor.
     *
     * @param Player $player
     */
    public function __construct(Player $player) {
        $this->player = $player;
    }
}