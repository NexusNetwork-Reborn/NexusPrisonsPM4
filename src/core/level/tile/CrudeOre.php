<?php
declare(strict_types=1);

namespace core\level\tile;

use core\level\block\Ore;
use core\level\LevelManager;
use core\player\NexusPlayer;
use pocketmine\block\BlockFactory;
use pocketmine\block\tile\Spawnable;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\world\World;

class CrudeOre extends Spawnable {

    const ORE = "Ore";

    const RARITY = "Rarity";

    const REFINED = "Refined";

    const AMOUNT = "Amount";

    /** @var int */
    private $ore = 0;

    /** @var string */
    private $rarity;

    /** @var bool */
    private $refined;

    /** @var float */
    private $amount;

    /** @var null|int */
    private $sources = null;

    /**
     * CrudeOre constructor.
     *
     * @param World $world
     * @param Vector3 $pos
     */
    public function __construct(World $world, Vector3 $pos) {
        parent::__construct($world, $pos);
        $this->getPosition()->getWorld()->scheduleDelayedBlockUpdate($this->getPosition(), 300);
    }

    public function recalculate(): void {
        if($this->canUpdate()) {
            $sources = 0;
            $bbs = $this->getBlock()->getCollisionBoxes();
            if(count($bbs) !== 1) {
                return;
            }
            $bb = $bbs[0];
            foreach(LevelManager::getNearbyTiles($this->getPosition()->getWorld(), $bb->expandedCopy(3, 3, 3)) as $t) {
                if($t instanceof OreGenerator) {
                    $ore = $t->getOre();
                    if($ore === -1 or $ore === null) {
                        continue;
                    }
                    if($t->getPosition()->distance($this->getPosition()) <= \core\level\block\CrudeOre::getRangeByOre($ore)) {
                        $ore = BlockFactory::getInstance()->get($this->getOre(), 0);
                        if(!$ore instanceof Ore) {
                            $this->setOre($t->getOre());
                        }
                        if($t->getOre() === $this->getOre()) {
                            $sources += $t->getStack();
                            continue;
                        }
                    }
                }
            }
            if($this->sources !== $sources) {
                if($sources === 0) {
                    $this->sources = null;
                }
                else {
                    $this->sources = $sources;
                }
            }
            if($this->sources === 0 and $this->amount < 1) {
                $this->ore = 0;
            }
        }
    }

    /**
     * @return bool
     */
    public function canUpdate(): bool {
        $pos = $this->getPosition();
        if(!$pos->getWorld()->isChunkLoaded($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)) {
            return false;
        }
        $bb = $this->getBlock()->getCollisionBoxes();
        if($bb === null) {
            return false;
        }
        $bb = $bb[0];
        foreach($this->getPosition()->getWorld()->getNearbyEntities($bb->expandedCopy(32, 32, 32)) as $entity) {
            if($entity instanceof NexusPlayer) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $item
     */
    public function setOre(int $item): void {
        $this->ore = $item;
    }

    /**
     * @return int
     */
    public function getOre(): int {
        return $this->ore;
    }

    /**
     * @param string $rarity
     */
    public function setRarity(string $rarity): void {
        $this->rarity = $rarity;
    }

    /**
     * @return string
     */
    public function getRarity(): string {
        return $this->rarity;
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

    /**
     * @param float $amount
     */
    public function setAmount(float $amount): void {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getAmount(): float {
        return $this->amount;
    }

    /**
     * @param int|null $sources
     */
    public function setSources(?int $sources): void {
        $this->sources = $sources;
    }

    /**
     * @return int|null
     */
    public function getSources(): ?int {
        return $this->sources;
    }

    /**
     * @param CompoundTag $nbt
     */
    public function readSaveData(CompoundTag $nbt): void {
        if(!$nbt->getTag(self::ORE) instanceof IntTag) {
            $nbt->setInt(self::ORE, $this->ore);
        }
        $this->ore = $nbt->getInt(self::ORE, $this->ore);
        if(!$nbt->getTag(self::RARITY) instanceof StringTag) {
            $nbt->setString(self::RARITY, $this->rarity);
        }
        $this->rarity = $nbt->getString(self::RARITY, $this->rarity);
        if(!$nbt->getTag(self::REFINED) instanceof ByteTag) {
            $nbt->setByte(self::REFINED, (int)$this->refined);
        }
        $this->refined = (bool)$nbt->getByte(self::REFINED, (int)$this->refined);
        if(!$nbt->getTag(self::AMOUNT) instanceof DoubleTag) {
            $nbt->setDouble(self::AMOUNT, $this->amount);
        }
        $this->amount = $nbt->getDouble(self::AMOUNT, $this->amount);
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function writeSaveData(CompoundTag $nbt): void {
        $nbt->setInt(self::ORE, $this->getOre());
        $nbt->setString(self::RARITY, $this->getRarity());
        $nbt->setByte(self::REFINED, (int)$this->isRefined());
        $nbt->setDouble(self::AMOUNT, $this->getAmount());
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function addAdditionalSpawnData(CompoundTag $nbt): void {
        $nbt->setInt(self::ORE, $this->getOre());
        $nbt->setString(self::RARITY, $this->getRarity());
        $nbt->setByte(self::REFINED, (int)$this->isRefined());
        $nbt->setDouble(self::AMOUNT, $this->getAmount());
    }
}