<?php
declare(strict_types=1);

namespace core\game\zone;

use pocketmine\world\Position;
use pocketmine\world\World;

class Zone {

    /** @var int */
    private $tier;

    /** @var Position */
    private $firstPosition;

    /** @var Position */
    private $secondPosition;

    /** @var World */
    private $level;

    /**
     * Zone constructor.
     *
     * @param int $tier
     * @param Position $firstPosition
     * @param Position $secondPosition
     *
     * @throws ZoneException
     */
    public function __construct(int $tier, Position $firstPosition, Position $secondPosition) {
        $this->firstPosition = $firstPosition->floor();
        $this->secondPosition = $secondPosition->floor();
        $this->tier = $tier;
        $this->level = $firstPosition->getWorld()->getFolderName() === $secondPosition->getWorld()->getFolderName() ? $firstPosition->getWorld() : null;
        if($this->level === null) {
            throw new ZoneException("Zone's first position's level does not equal the second position's level.");
        }
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function isPositionInside(Position $position): bool {
        $level = $position->getWorld();
        $position = $position->floor();
        $firstPosition = $this->firstPosition;
        $secondPosition = $this->secondPosition;
        $minX = floor(min($firstPosition->getX(), $secondPosition->getX()));
        $maxX = floor(max($firstPosition->getX(), $secondPosition->getX()));
        $minY = floor(min($firstPosition->getY(), $secondPosition->getY()));
        $maxY = floor(max($firstPosition->getY(), $secondPosition->getY()));
        $minZ = floor(min($firstPosition->getZ(), $secondPosition->getZ()));
        $maxZ = floor(max($firstPosition->getZ(), $secondPosition->getZ()));
        return $minX <= $position->getX() and $maxX >= $position->getX() and $minY <= $position->getY() and
            $maxY >= $position->getY() and $minZ <= $position->getZ() and $maxZ >= $position->getZ() and
            $this->level->getFolderName() === $level->getFolderName();
    }

    /**
     * @return Position
     */
    public function getFirstPosition(): Position {
        return $this->firstPosition;
    }

    /**
     * @return Position
     */
    public function getSecondPosition(): Position {
        return $this->secondPosition;
    }

    /**
     * @return World
     */
    public function getLevel(): World {
        return $this->level;
    }

    /**
     * @return int
     */
    public function getTierId(): int {
        return $this->tier;
    }
}