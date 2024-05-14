<?php
declare(strict_types=1);

namespace core\player\rpg\event;

use core\player\NexusPlayer;
use pocketmine\event\player\PlayerEvent;

class LevelUpEvent extends PlayerEvent {

    /** @var int */
    private $oldLevel;

    /** @var int */
    private $newLevel;

    /**
     * LevelUpEvent constructor.
     *
     * @param NexusPlayer $player
     * @param int $oldLevel
     * @param int $newLevel
     */
    public function __construct(NexusPlayer $player, int $oldLevel, int $newLevel) {
        $this->player = $player;
        $this->oldLevel = $oldLevel;
        $this->newLevel = $newLevel;
    }

    /**
     * @return int
     */
    public function getOldLevel(): int {
        return $this->oldLevel;
    }

    /**
     * @return int
     */
    public function getNewLevel(): int {
        return $this->newLevel;
    }
}