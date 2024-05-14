<?php

declare(strict_types = 1);

namespace core\game\gamble\event;

use core\player\NexusPlayer;
use pocketmine\event\player\PlayerEvent;

class LotteryBuyEvent extends PlayerEvent {

    /** @var int */
    private $amount;

    /**
     * CrateOpenEvent constructor.
     *
     * @param NexusPlayer $player
     * @param int $amount
     */
    public function __construct(NexusPlayer $player, int $amount) {
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