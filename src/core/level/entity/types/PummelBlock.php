<?php

namespace core\level\entity\types;

use core\display\animation\entity\AnimationEntity;
use core\player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\player\Player;
use pocketmine\world\Position;

class PummelBlock extends Entity implements AnimationEntity {

    /** @var bool */
    public $canCollide = true;

    /** @var int */
    protected $gravity = 0;

    /** @var int */
    protected $drag = 0;

    /** @var float */
    protected $speed = 0.05;

    /** @var null|Position */
    protected $designatedPosition = null;

    /** @var null|Block */
    protected $block = null;

    /** @var int */
    protected $delay = 60;

    /**
     * @return EntitySizeInfo
     */
    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(0.001, 0.001);
    }

    /**
     * @return string
     */
    public static function getNetworkTypeId(): string {
        return EntityIds::ARMOR_STAND;
    }

    /**
     * @param NexusPlayer $owner
     * @param Entity $target
     * @param Block $block
     * @param Position $position
     * @param int $attackDelay
     *
     * @return PummelBlock
     */
    public static function create(NexusPlayer $owner, Entity $target, Block $block, Position $position, int $attackDelay): PummelBlock {
        $entity = new PummelBlock(Location::fromObject($position, $position->getWorld()));
        $entity->setOwningEntity($owner);
        $entity->setPosition($position);
        $entity->recalculateBoundingBox();
        $entity->setTargetEntity($target);
        $entity->setBlock($block);
        $entity->setAttackDelay($attackDelay);
        $entity->initialize(Position::fromObject($position->add(0, 4 + (mt_rand(1, 10) / 10), 0), $position->getWorld()));
        $entity->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, $entity->getInitialSizeInfo()->getWidth());
        $entity->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, $entity->getInitialSizeInfo()->getHeight());
        $entity->getNetworkProperties()->setByte(EntityMetadataProperties::ARMOR_STAND_POSE_INDEX, 8);
        $entity->setInvisible();
        return $entity;
    }

    /**
     * @param int $tickDiff
     *
     * @return bool
     */
    protected function entityBaseTick(int $tickDiff = 1): bool {
        if($this->isClosed()) {
            return false;
        }
        if($this->designatedPosition === null and $this->getTargetEntity() === null) {
            $this->flagForDespawn();
            return false;
        }
        if($this->designatedPosition !== null) {
            $position = $this->designatedPosition;
        }
        elseif($this->getTargetEntity() !== null) {
            $target = $this->getTargetEntity();
            $position = $target->getPosition();
            $position = Position::fromObject($position->add(0, 0.75, 0), $this->getWorld());
            if($position->distance($this->getPosition()) >= 35) {
                $this->flagForDespawn();
                return false;
            }
        }
        else {
            return false;
        }
        $this->motion = new Vector3(0, 0, 0);
        $x = $position->x - $this->getPosition()->x;
        $y = $position->y - $this->getPosition()->y;
        $z = $position->z - $this->getPosition()->z;
        if($x * $x + $z * $z < 1.2) {
            $this->motion->x = 0;
            $this->motion->z = 0;
            if($this->designatedPosition !== null) {
                if($this->delay-- <= 0) {
                    $this->designatedPosition = null;
                    $this->speed = 8;
                }
                else {
                    $this->location->yaw = ($this->location->yaw + 10) > 360 ? 0 : ($this->location->yaw + 10);
                    $this->setPosition($this->designatedPosition);
                    $this->updateMovement();
                    parent::entityBaseTick($tickDiff);
                    return $this->isAlive();
                }
            }
            else {
                $target = $this->getTargetEntity();
                $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_CUSTOM, 3, [], 0.3);
                $ev->call();
                if(!$ev->isCancelled()) {
                    $target->attack($ev);
                }
                $this->flagForDespawn();
            }
        }
        else {
            $this->motion->x = $this->speed * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->y = $this->speed * 0.15 * $y;
            $this->motion->z = $this->speed * 0.15 * ($z / (abs($x) + abs($z)));
        }
        if($y * $y > 0.6 and $this->motion->x === 0 and $this->motion->z === 0) {
            $this->motion->y = $this->speed * 0.15 * $y;
        }
        elseif($y * $y < 0.6 and $this->motion->x === 0 and $this->motion->z === 0) {
            return true;
        }
        else {
            $this->motion->x = $this->speed * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->y = $this->speed * 0.15 * ($y / (abs($x) + abs($z)));
            $this->motion->z = $this->speed * 0.15 * ($z / (abs($x) + abs($z)));
        }
        $this->location->yaw = rad2deg(atan2(-$x, $z)) - 35;
        $this->updateMovement();
        parent::entityBaseTick($tickDiff);
        return $this->isAlive();
    }

    /**
     * @param Position|null $position
     */
    public function initialize(?Position $position = null) {
        $this->designatedPosition = $position;
    }

    /**
     * @return bool
     */
    public function isFireProof(): bool {
        return true;
    }

    /**
     * @return bool
     */
    public function canBeCollidedWith(): bool {
        return true;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function canCollideWith(Entity $entity): bool {
        return false;
    }

    /**
     * @return bool
     */
    public function canBeMovedByCurrents(): bool {
        return false;
    }

    /**
     * @return bool
     */
    public function canSaveWithChunk(): bool {
        return false;
    }

    /**
     * @return bool
     */
    public function canBreathe(): bool {
        return true;
    }

    /**
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source): void {

    }

    protected function applyGravity(): void {

    }

    protected function checkBlockCollision(): void {

    }

    /**
     * @return Block|null
     */
    public function getBlock(): ?Block {
        return $this->block;
    }

    /**
     * @param Block|null $block
     */
    public function setBlock(?Block $block): void {
        $this->block = $block;
    }

    /**
     * @param Player $player
     */
    protected function sendSpawnPacket(Player $player): void {
        parent::sendSpawnPacket($player);
        if($this->block !== null) {
            $pk = new MobEquipmentPacket();
            $pk->actorRuntimeId = $this->getId();
            $pk->inventorySlot = $pk->hotbarSlot = 0;
            $pk->item = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($this->block->asItem()));
            $player->getNetworkSession()->sendDataPacket($pk);
        }
    }

    /**
     * @param int $attackDelay
     */
    public function setAttackDelay(int $attackDelay): void {
        $this->delay += $attackDelay;
    }
}