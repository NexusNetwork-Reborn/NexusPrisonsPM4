<?php
declare(strict_types=1);

namespace core\game\combat\merchants\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class KillMerchantEvent extends PlayerEvent {

    /** @var int */
    private $ore;

    /**
     * SatchelLevelUpEvent constructor.
     *
     * @param Player $player
     * @param int $ore
     */
    public function __construct(Player $player, int $ore) {
        $this->player = $player;
        $this->ore = $ore;
    }

    /**
     * @return int
     */
    public function getOre(): int {
        return $this->ore;
    }
}