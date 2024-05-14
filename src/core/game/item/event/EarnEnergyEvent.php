<?php
declare(strict_types=1);

namespace core\game\item\event;

use core\game\item\types\CustomItem;
use pocketmine\event\player\PlayerEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class EarnEnergyEvent extends PlayerEvent {

    /** @var int */
    private $amount;

    /**
     * EarnEnergyEvent constructor.
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

    public function addAmount(int $amount): void {
        $this->amount += $amount;
    }
}