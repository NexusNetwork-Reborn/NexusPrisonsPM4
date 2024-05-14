<?php

namespace core\game\plots\plot;

use core\level\LevelManager;
use core\Nexus;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Tile;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\AxisAlignedBB;
use pocketmine\world\Position;
use pocketmine\world\World;

class Plot {

    const DEFAULT_EXPIRATION = 604800;

    const MAX_MEMBERS = 20;

    /** @var int */
    private $id;

    /** @var Position */
    private $firstPosition;

    /** @var Position */
    private $secondPosition;

    /** @var World */
    private $world;

    /** @var Position */
    private $spawn;

    /** @var null|PlotOwner */
    private $owner;

    /** @var int */
    private $expiration;

    /** @var AxisAlignedBB */
    private $boundingBox;

    /**
     * Plot constructor.
     *
     * @param int $id
     * @param Position $firstPosition
     * @param Position $secondPosition
     * @param World $world
     * @param Position $spawn
     * @param int $expiration
     * @param PlotOwner|null $owner
     */
    public function __construct(int $id, Position $firstPosition, Position $secondPosition, World $world, Position $spawn, int $expiration, ?PlotOwner $owner = null) {
        $this->id = $id;
        $this->firstPosition = $firstPosition;
        $this->secondPosition = $secondPosition;
        $this->world = $world;
        $this->spawn = $spawn;
        $this->expiration = $expiration;
        $this->owner = $owner;
        if($this->owner !== null) {
            $this->owner->setPlot($this);
        }
        $minX = floor(min($firstPosition->getX(), $secondPosition->getX()));
        $maxX = floor(max($firstPosition->getX(), $secondPosition->getX()));
        $minY = floor(min($firstPosition->getY(), $secondPosition->getY()));
        $maxY = floor(max($firstPosition->getY(), $secondPosition->getY()));
        $minZ = floor(min($firstPosition->getZ(), $secondPosition->getZ()));
        $maxZ = floor(max($firstPosition->getZ(), $secondPosition->getZ()));
        $this->boundingBox = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
    }

    public function tickExpiration(): void {
        if($this->owner === null) {
            return;
        }
        if(!empty($this->owner->getOnlineUsers())) {
            $this->expiration = time();
            $this->owner->scheduleUpdate();
        }
    }

    public function clearPlot(): void {
        $minX = floor(min($this->firstPosition->getX(), $this->secondPosition->getX()));
        $maxX = floor(max($this->firstPosition->getX(), $this->secondPosition->getX()));
        $minY = floor(min($this->firstPosition->getY(), $this->secondPosition->getY()));
        $maxY = floor(max($this->firstPosition->getY(), $this->secondPosition->getY()));
        $minZ = floor(min($this->firstPosition->getZ(), $this->secondPosition->getZ()));
        $maxZ = floor(max($this->firstPosition->getZ(), $this->secondPosition->getZ()));
        for($x = $minX; ($x - $maxX) <= 16; $x += 16) {
            for($z = $minZ; ($z - $maxZ) <= 16; $z += 16) {
                $this->world->loadChunk($x >> 4, $z >> 4);
            }
        }
        for($x = $minX; $x <= $maxX; $x++) {
            for($y = $minY; $y <= $maxY; $y++) {
                for($z = $minZ; $z <= $maxZ; $z++) {
                    if($this->world->getBlockAt($x, $y, $z)->getId() === BlockLegacyIds::AIR) {
                        continue;
                    }
                    $tile = $this->world->getTileAt($x, $y, $z);
                    if($tile !== null) {
                        $this->world->removeTile($tile);
                    }
                    $this->world->setBlockAt($x, $y, $z, VanillaBlocks::AIR());
                }
            }
        }
    }

    public function delete(): void {
        $this->owner = null;
        $database = Nexus::getInstance()->getMySQLProvider()->getDatabase();
        $stmt = $database->prepare("DELETE FROM plots WHERE id = ?");
        $stmt->bind_param("s", $this->id);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function isPositionInside(Position $position): bool {
        $world = $position->getWorld();
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
            $this->world->getFolderName() === $world->getFolderName();
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
    public function getWorld(): World {
        return $this->world;
    }

    /**
     * @return Position
     */
    public function getSpawn(): Position {
        return $this->spawn;
    }

    /**
     * @return PlotOwner|null
     */
    public function getOwner(): ?PlotOwner {
        return $this->owner;
    }

    /**
     * @param PlotOwner|null $owner
     */
    public function setOwner(?PlotOwner $owner): void {
        $this->owner = $owner;
        if($owner !== null) {
            $this->owner->setPlot($this);
        }
    }

    /**
     * @return int
     */
    public function getExpiration(): int {
        return self::DEFAULT_EXPIRATION - (time() - $this->expiration);
    }

    /**
     * @return int
     */
    public function getExpirationTime(): int {
        return $this->expiration;
    }

    /**
     * @param int $expiration
     */
    public function setExpirationTime(int $expiration): void {
        $this->expiration = $expiration;
    }

    /**
     * @return Tile[]
     */
    public function getTiles(): array {
        return LevelManager::getNearbyTiles($this->world, $this->boundingBox);
    }

    /**
     * @return Block[]
     */
    public function getFloorBlocks() : array
    {
        $minX = floor(min($this->firstPosition->getX(), $this->secondPosition->getX()));
        $maxX = floor(max($this->firstPosition->getX(), $this->secondPosition->getX()));
        $minY = floor(min($this->firstPosition->getY(), $this->secondPosition->getY()));
        $minZ = floor(min($this->firstPosition->getZ(), $this->secondPosition->getZ()));
        $maxZ = floor(max($this->firstPosition->getZ(), $this->secondPosition->getZ()));

        for($x = $minX; ($x - $maxX) <= 16; $x += 16) {
            for($z = $minZ; ($z - $maxZ) <= 16; $z += 16) {
                $this->world->loadChunk($x >> 4, $z >> 4);
            }
        }

        /** @var Block[] $blocks */
        $blocks = [];

        for($x = $minX; $x <= $maxX; $x++) {
            for($z = $minZ; $z <= $maxZ; $z++) {
                $blocks[] = $this->world->getBlockAt($x, $minY - 1, $z);
            }
        }

        return $blocks;
    }

    /**
     * @param Block $block
     */
    public function setFloorBlocks(Block $block) : void
    {
        $minX = floor(min($this->firstPosition->getX(), $this->secondPosition->getX()));
        $maxX = floor(max($this->firstPosition->getX(), $this->secondPosition->getX()));
        $minZ = floor(min($this->firstPosition->getZ(), $this->secondPosition->getZ()));
        $maxZ = floor(max($this->firstPosition->getZ(), $this->secondPosition->getZ()));

        for($x = $minX; ($x - $maxX) <= 16; $x += 16) {
            for($z = $minZ; ($z - $maxZ) <= 16; $z += 16) {
                $this->world->loadChunk($x >> 4, $z >> 4);
            }
        }

        foreach ($this->getFloorBlocks() as $b) {
            if($b->asItem()->getId() === BlockLegacyIds::WOODEN_PLANKS || $b->asItem()->getId() === VanillaBlocks::POLISHED_ANDESITE()->asItem()->getId()) continue;
            $b->getPosition()->getWorld()->setBlock($b->getPosition()->asVector3(), $block);
        }
    }
}