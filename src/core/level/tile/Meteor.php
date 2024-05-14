<?php

declare(strict_types=1);

namespace core\level\tile;

use pocketmine\block\tile\Spawnable;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class Meteor extends Spawnable {

    /**
     * Meteorite constructor.
     *
     * @param World $world
     * @param Vector3 $pos
     */
    public function __construct(World $world, Vector3 $pos) {
        parent::__construct($world, $pos);
        $this->getPosition()->getWorld()->scheduleDelayedBlockUpdate($this->getPosition(), mt_rand(4800, 5600));
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt): void {
    }

    protected function writeSaveData(CompoundTag $nbt): void {
    }

    public function readSaveData(CompoundTag $nbt): void {
    }
}