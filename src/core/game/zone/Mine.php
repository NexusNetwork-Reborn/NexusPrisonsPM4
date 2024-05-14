<?php
declare(strict_types=1);

namespace core\game\zone;

use core\player\NexusPlayer;
use pocketmine\item\Item;
use pocketmine\world\Position;

class Mine {

    /** @var int */
    private $level;

    /** @var Position */
    private $position;

    /** @var string */
    private $name;

    /** @var Item */
    private $item;

    /**
     * Mine constructor.
     *
     * @param string $name
     * @param int $level
     * @param Item $item
     * @param Position $position
     */
    public function __construct(string $name, int $level, Item $item, Position $position) {
        $this->name = $name;
        $this->level = $level;
        $this->item = $item;
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    /**
     * @return int
     */
    public function getLevel(): int {
        return $this->level;
    }

    /**
     * @return Position
     */
    public function getPosition(): Position {
        return $this->position;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public function canAccess(NexusPlayer $player): bool {
        if(!$player->isLoaded()) {
            return false;
        }
        return $player->getDataSession()->getPrestige() > 0 or $player->getDataSession()->getTotalXPLevel() >= $this->level;
    }
}