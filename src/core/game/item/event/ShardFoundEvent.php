<?php
declare(strict_types=1);

namespace core\game\item\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class ShardFoundEvent extends PlayerEvent {

    /** @var int */
    private $amount;

    /**
     * ShardFoundEvent constructor.
     *
     * @param Player $player
     * @param int $amount
     */
    public function __construct(Player $player, int $amount) {
        $this->player = $player;
        $this->amount = $amount;
    }

    /**
     * @return int
     */
    public function getAmount(): int {
        return $this->amount;
    }
}