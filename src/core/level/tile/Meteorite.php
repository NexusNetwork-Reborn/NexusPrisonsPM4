<?php

declare(strict_types=1);

namespace core\level\tile;

use pocketmine\block\tile\Spawnable;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\world\World;

class Meteorite extends Spawnable {

    const STACK = "Stack";

    const REFINED = "Refined";

    /** @var int */
    private $stack = 1;

    /** @var bool */
    private $refined = false;

    /**
     * Meteorite constructor.
     *
     * @param World $world
     * @param Vector3 $pos
     */
    public function __construct(World $world, Vector3 $pos) {
        parent::__construct($world, $pos);
        $this->getPosition()->getWorld()->scheduleDelayedBlockUpdate($this->getPosition(), 300);
    }

    /**
     * @return int
     */
    public function getStack(): int {
        return $this->stack;
    }

    /**
     * @param int $stack
     */
    public function setStack(int $stack): void {
        $this->stack = $stack;
    }

    /**
     * @param bool $refined
     */
    public function setRefined(bool $refined): void {
        $this->refined = $refined;
    }

    /**
     * @return bool
     */
    public function isRefined(): bool {
        return $this->refined;
    }

    public function decay(): void {
        --$this->stack;
    }

    /**
     * @param CompoundTag $nbt
     */
    public function readSaveData(CompoundTag $nbt): void {
        if(!$nbt->getTag(self::STACK) instanceof IntTag) {
            $nbt->setInt(self::STACK, $this->stack);
        }
        $this->stack = $nbt->getInt(self::STACK, $this->stack);
        if(!$nbt->getTag(self::REFINED) instanceof ByteTag) {
            $nbt->setByte(self::REFINED, (int)$this->refined);
        }
        $this->refined = (bool)$nbt->getByte(self::REFINED, (int)$this->refined);
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function writeSaveData(CompoundTag $nbt): void {
        $nbt->setInt(self::STACK, $this->getStack());
        $nbt->setByte(self::REFINED, (int)$this->isRefined());
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function addAdditionalSpawnData(CompoundTag $nbt): void {
        $nbt->setInt(self::STACK, $this->getStack());
        $nbt->setByte(self::REFINED, (int)$this->isRefined());
    }
}