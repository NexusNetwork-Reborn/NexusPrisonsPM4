<?php
declare(strict_types=1);

namespace core\level\tile;

use core\level\block\Ore;
use core\level\LevelManager;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Spawnable;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\world\World;

class OreGenerator extends Spawnable {

    const ORE = "Ore";

    const STACK = "Stack";

    /** @var ?int */
    private $ore;

    /** @var int */
    private $stack = 1;

    /**
     * OreGenerator constructor.
     *
     * @param World $world
     * @param Vector3 $pos
     */
    public function __construct(World $world, Vector3 $pos) {
        parent::__construct($world, $pos);
        $this->getPosition()->getWorld()->scheduleDelayedBlockUpdate($this->getPosition(), 1200);
    }

    /**
     * @param int $ore
     *
     * @return float
     */
    public static function getAmountGenerated(int $ore): float {
        switch($ore) {
            case BlockLegacyIds::COAL_ORE:
                return 0.025;
                break;
            case BlockLegacyIds::IRON_ORE:
                return 0.0125;
                break;
            case BlockLegacyIds::LAPIS_ORE:
                return 0.0085;
                break;
            case BlockLegacyIds::REDSTONE_ORE:
                return 0.006;
                break;
            case BlockLegacyIds::GOLD_ORE:
                return 0.00425;
                break;
            case BlockLegacyIds::DIAMOND_ORE:
                return 0.0025;
                break;
            case BlockLegacyIds::EMERALD_ORE:
                return 0.001;
                break;
            default:
                return -1;
                break;
        }
    }

    /**
     * @param int $minutes
     */
    public function process(int $minutes): void {
        if($this->ore === null or $this->ore === -1) {
            return;
        }
        $block = $this->getBlock();
        $bbs = $block->getCollisionBoxes();
        if(count($bbs) !== 1) {
            return;
        }
        $bb = $bbs[0];
        $amount = self::getAmountGenerated($this->ore);
        if($amount <= 0) {
            return;
        }
        $amount *= $this->stack;
        $amount *= $minutes;
        $r = \core\level\block\CrudeOre::getRangeByOre($this->getOre());
        foreach(LevelManager::getNearbyTiles($this->getPosition()->getWorld(), $bb->expandedCopy($r, $r, $r)) as $tile) {
            $add = $amount;
            if($tile instanceof CrudeOre) {
                if($tile->getOre() === $this->ore) {
                    if($tile->isRefined()) {
                        $add /= 2;
                    }
                    $tile->setAmount($tile->getAmount() + $add);
                    continue;
                }
            }
        }
        return;
    }

    /**
     * @param int|null $item
     */
    public function setOre(?int $item): void {
        $this->ore = $item;
    }

    /**
     * @return int|null
     */
    public function getOre(): ?int {
        return $this->ore;
    }

    /**
     * @param int $stack
     */
    public function setStack(int $stack): void {
        $block = $this->getBlock();
        $bbs = $block->getCollisionBoxes();
        if(count($bbs) !== 1) {
            return;
        }
        $bb = $bbs[0];
        $diff = $stack - $this->stack;
        $this->stack = $stack;
        $r = \core\level\block\CrudeOre::getRangeByOre($this->getOre());
        foreach(LevelManager::getNearbyTiles($this->getPosition()->getWorld(), $bb->expandedCopy($r, $r, $r)) as $tile) {
            if($tile instanceof CrudeOre) {
                if($tile->getOre() === $this->ore) {
                    $tile->setSources($tile->getSources() + $diff);
                    continue;
                }
                $ore = BlockFactory::getInstance()->get($tile->getOre(), 0);
                if(!$ore instanceof Ore) {
                    $tile->setOre($this->ore);
                    $tile->setSources($this->stack);
                }
            }
        }
    }

    /**
     * @return int
     */
    public function getStack(): int {
        return $this->stack;
    }

    /**
     * @param CompoundTag $nbt
     */
    public function readSaveData(CompoundTag $nbt): void {
        if(!$nbt->getTag(self::ORE) instanceof IntTag) {
            $nbt->setInt(self::ORE, $this->ore);
        }
        $this->ore = $nbt->getInt(self::ORE, $this->ore);
        if($this->ore === -1) {
            $this->ore = null;
        }
        if(!$nbt->getTag(self::STACK) instanceof LongTag) {
            $nbt->setLong(self::STACK, $this->stack);
        }
        $this->stack = $nbt->getLong(self::STACK, $this->stack);
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function writeSaveData(CompoundTag $nbt): void {
        if($this->ore === null) {
            $this->ore = -1;
        }
        $nbt->setInt(self::ORE, $this->getOre());
        $nbt->setLong(self::STACK, $this->getStack());
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function addAdditionalSpawnData(CompoundTag $nbt): void {
        if($this->ore === null) {
            $this->ore = -1;
        }
        $nbt->setInt(self::ORE, $this->getOre());
        $nbt->setDouble(self::STACK, $this->getStack());
    }
}