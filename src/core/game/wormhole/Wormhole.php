<?php

namespace core\game\wormhole;

use core\command\inventory\WarpMenuInventory;
use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class Wormhole {

    /** @var Position */
    private $center;

    /** @var int */
    private $radius;

    /** @var int */
    private $rarityLimit;

    /**
     * Wormhole constructor.
     *
     * @param Position $center
     * @param int $radius
     * @param int $rarityLimit
     */
    public function __construct(Position $center, int $radius, int $rarityLimit = Enchantment::LEGENDARY) {
        $this->center = $center;
        $this->radius = $radius;
        $this->rarityLimit = $rarityLimit;
    }

    /**
     * @return Position
     */
    public function getCenter(): Position {
        return $this->center;
    }

    /**
     * @return int
     */
    public function getRadius(): int {
        return $this->radius;
    }

    /**
     * @return int
     */
    public function getRarityLimit(): int {
        return $this->rarityLimit;
    }

    /**
     * @param NexusPlayer $player
     * @param Item $item
     *
     * @return bool
     */
    public function canUse(NexusPlayer $player, Item $item, bool $message = true, bool $ignoreKey = true): bool {
        $distanceCheck = $player->getPosition()->distance($this->center) < ($this->radius * 1.5);
        if($player->getWorld()->getFolderName() == "executive") {
            $task = WarpMenuInventory::getExecutiveSession($player);
            if($task === null) {
                return false;
            }
            if(!$task->isWormholeUnlocked() && $distanceCheck) {
                if($message) $player->sendMessage(TextFormat::RED . "You have to use an Interdimensional Key first to unlock the Executive Wormhole!");
                if($ignoreKey) {
                    return false;
                }
            }
            return $distanceCheck;
        }
        return $distanceCheck && $player->getWorld()->getFolderName() === $this->getCenter()->getWorld()->getFolderName();
    }
}