<?php

namespace libs\utils;

use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

class LevelUtils {

    /**
     * Gets the block relative to the provided face.
     *
     * @param Block $block - Block to use
     * @param int $face - the face.
     *
     * @return Block
     */
    public static function getRelativeBlock(Block $block, int $face): Block {
        $newVector = $block->getSide($face);
        return $block->getPosition()->getWorld()->getBlock($newVector->getPosition());
    }

    /**
     * Alias for Level::getBlockAt()
     *
     * @param Position $pos - Position to get the block at.
     *
     * @return Block|null
     */
    public static function getBlockWhere(Position $pos): ?Block {
        $level = $pos->getWorld();
        if($level === null) {
            return null;
        }
        else {
            return $level->getBlock($pos);
        }
    }

    /**
     * @param World $level
     * @param Vector3|null $spawn
     * @param int|null $maxY
     *
     * @return Position
     */
    public static function getSafeSpawn(World $level, ?Vector3 $spawn = null, ?int $maxY = null): Position {
        if(!($spawn instanceof Vector3) or $spawn->y < 1) {
            $spawn = $level->getSpawnLocation();
        }
        $max = $maxY !== null ? $maxY : World::Y_MAX;
        $v = $spawn->floor();
        $chunk = $level->getOrLoadChunkAtPosition($v);
        $x = (int)$v->x;
        $z = (int)$v->z;
        if($chunk !== null) {
            $y = (int)$max - 2;
            $wasAir = ($chunk->getFullBlock($x & 0x0f, $y - 1, $z & 0x0f) === 0);
            for(; $y > 0; --$y) {
                if($level->getBlockAt($x, $y, $z)->isFullCube()) {
                    if($wasAir) {
                        $y++;
                        break;
                    }
                }
                else {
                    $wasAir = true;
                }
            }
            for(; $y >= 0 and $y < $max; ++$y) {
                if(!$level->getBlockAt($x, $y + 1, $z)->isFullCube()) {
                    if(!$level->getBlockAt($x, $y, $z)->isFullCube()) {
                        return new Position($spawn->x, $y === (int)$spawn->y ? $spawn->y : $y, $spawn->z, $level);
                    }
                }
                else {
                    ++$y;
                }
            }
            $v->y = $y;
        }
        return new Position($spawn->x, $v->y, $spawn->z, $level);
    }
}