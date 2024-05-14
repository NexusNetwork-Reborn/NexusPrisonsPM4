<?php
declare(strict_types=1);

namespace core\game\item\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class DiscoverContrabandEvent extends PlayerEvent {

    /** @var string */
    private $rarity;

    /**
     * DiscoverContrabandEvent constructor.
     *
     * @param Player $player
     * @param string $rarity
     */
    public function __construct(Player $player, string $rarity) {
        $this->player = $player;
        $this->rarity = $rarity;
    }

    /**
     * @return string
     */
    public function getRarity(): string {
        return $this->rarity;
    }
}