<?php

namespace core\game\zone;

use core\game\badlands\bandit\BanditBoss;
use core\game\badlands\bandit\EliteBandit;
use core\game\badlands\bandit\XPBandit;
use core\game\item\types\vanilla\Armor;
use core\player\NexusPlayer;
use pocketmine\block\Air;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\world\format\BiomeArray;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\world\WorldException;

class BadlandsRegion
{
    /** @var int */
    private $tier;

    /** @var Position */
    private $spawnPosition;

    /** @var Position */
    public $firstPosition;

    /** @var Position */
    public $secondPosition;

    /** @var World */
    private $level;

    public function __construct(int $tier, Position $spawnPosition, Position $firstPosition, Position $secondPosition, World $world) {
        $this->tier = $tier;
        $this->spawnPosition = $spawnPosition;
        $this->firstPosition = $firstPosition;
        $this->secondPosition = $secondPosition;
        $this->level = $world;
    }

    /**
     * @return int
     */
    public function getTier() : int
    {
        return $this->tier;
    }

    /**
     * @return Position
     */
    public function getSpawnPosition() : Position
    {
        return $this->spawnPosition;
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
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public function canAccess(NexusPlayer $player): bool {
        if(!$player->isLoaded()) {
            return false;
        }
        return $player->getDataSession()->getPrestige() > 0 or $player->getDataSession()->getTotalXPLevel() >= $this->tierToLevel($this->tier);
    }

    private function tierToLevel(int $tier) : int
    {
        return match ($tier) {
            Armor::TIER_CHAIN => 1,
            Armor::TIER_GOLD => 30,
            Armor::TIER_IRON => 50,
            default => 90,
        };
    }

    /**
     * @return Living[]
     */
    public function getBanditsInside(): array {
        $world = $this->getSpawnPosition()->getWorld();
        $bb = new AxisAlignedBB(min($this->firstPosition->getX(), $this->secondPosition->getX()), 1, min($this->firstPosition->getZ(), $this->secondPosition->getZ()), max($this->firstPosition->getX(), $this->secondPosition->getX()), 300, max($this->firstPosition->getZ(), $this->secondPosition->getZ()));
        $array = [];
        foreach ($world->getCollidingEntities($bb) as $entity){
            if ($entity instanceof BanditBoss or $entity instanceof XPBandit or $entity instanceof EliteBandit){
                $array[] = $entity;
            }
        }
        return $array;
    }

    /**
     * @return Location
     * This is used for spawning bandits
     */
    public function generateRandomPos() : Location
    {
        $firstPosition = $this->firstPosition;
        $secondPosition = $this->secondPosition;

        $minX = (int)floor(min($firstPosition->getX(), $secondPosition->getX()));
        $maxX = (int)floor(max($firstPosition->getX(), $secondPosition->getX()));
        $minY = (int)floor(min($firstPosition->getY(), $secondPosition->getY()));
        $maxY = (int)floor(max($firstPosition->getY(), $secondPosition->getY()));
        $minZ = (int)floor(min($firstPosition->getZ(), $secondPosition->getZ()));
        $maxZ = (int)floor(max($firstPosition->getZ(), $secondPosition->getZ()));

        $x = mt_rand($minX, $maxX);
        $z = mt_rand($minZ, $maxZ);
        $y = mt_rand($minY, $maxY);

        while(!$firstPosition->getWorld()->getBlockAt($x, $y, $z) instanceof Air) {
            $x = mt_rand($minX, $maxX);
            $z = mt_rand($minZ, $maxZ);
            $y = mt_rand($minY, $maxY);
        }
        if (!$firstPosition->getWorld()->isChunkLoaded($x >> 4, $z >> 4) or !$firstPosition->getWorld()->isChunkGenerated($x >> 4, $z >> 4)){
            $firstPosition->getWorld()->loadChunk($x >> 4, $z >> 4);
        }
        try {
            $y = $firstPosition->getWorld()->getHighestBlockAt($x, $z) + 1;
        }catch (WorldException){
            $y = 64;
        }
        return new Location($x, $y, $z, $firstPosition->getWorld(), 0,0);
    }

    /**
     * @return World
     */
    public function getLevel(): World
    {
        return $this->level;
    }
}