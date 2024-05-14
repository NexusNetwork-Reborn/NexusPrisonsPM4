<?php

declare(strict_types = 1);

namespace core\game\combat\koth\event;

use core\player\NexusPlayer;
use pocketmine\event\player\PlayerEvent;

class KOTHCaptureEvent extends PlayerEvent {

    /**
     * CrateOpenEvent constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $this->player = $player;
    }
}