<?php
declare(strict_types=1);

namespace core\game\rewards;

use core\player\NexusPlayer;
use pocketmine\item\Item;

class Reward {

    /** @var string */
    private $name;

    /** @var callable */
    private $callback;

    /** @var int */
    private $chance;

    /**
     * Reward constructor.
     *
     * @param string $name
     * @param callable $callable
     * @param int $chance
     */
    public function __construct(string $name, callable $callable, int $chance) {
        $this->name = $name;
        $this->callback = $callable;
        $this->chance = $chance;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @param NexusPlayer|null $player
     *
     * @return Item
     */
    public function executeCallback(?NexusPlayer $player = null): Item {
        $callable = $this->callback;
        return $callable($player);
    }

    /**
     * @return int
     */
    public function getChance(): int {
        return $this->chance;
    }
}