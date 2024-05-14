<?php
declare(strict_types=1);

namespace core\game\item\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class SatchelLevelUpEvent extends PlayerEvent {

    /** @var int */
    private $levels;

    /**
     * SatchelLevelUpEvent constructor.
     *
     * @param Player $player
     * @param int $levels
     */
    public function __construct(Player $player, int $levels) {
        $this->player = $player;
        $this->levels = $levels;
    }

    /**
     * @return int
     */
    public function getLevels(): int {
        return $this->levels;
    }
}