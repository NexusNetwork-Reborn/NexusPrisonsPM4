<?php

namespace core\level;

use pocketmine\world\TickingChunkLoader;

class FakeChunkLoader implements TickingChunkLoader {

    /** @var int */
    private $x;

    /** @var int */
    private $z;

    public function __construct(int $chunkX, int $chunkZ) {
        $this->x = $chunkX << 4;
        $this->z = $chunkZ << 4;
    }

    /**
     * @return float
     */
    public function getX(): float {
        return (float)$this->x;
    }

    /**
     * @return float
     */
    public function getZ(): float {
        return (float)$this->z;
    }
}