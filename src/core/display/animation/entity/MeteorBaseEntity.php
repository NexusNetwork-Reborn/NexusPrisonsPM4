<?php

namespace core\display\animation\entity;

use core\display\animation\AnimationException;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use pocketmine\world\Position;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class MeteorBaseEntity extends Entity implements AnimationEntity {

    /** @var bool */
    public $canCollide = false;

    /** @var int */
    protected $gravity = 0;

    /** @var int */
    protected $drag = 0;

    /** @var null|float */
    protected $speed = null;

    /** @var null|Position */
    protected $designatedPosition = null;

    /** @var bool */
    protected $scheduleDespawn = false;

    /** @var int */
    protected $delay = 0;

    /** @var UuidInterface */
    protected $uuid;

    /** @var Skin */
    protected $skin;

    /** @var bool */
    protected $finish;

    /** @var Position */
    protected $pos;

    /** @var Position */
    protected $originalPos;

    /** @var null|callable */
    protected $callable = null;

    /** @var null|callable */
    protected $onFinish = null;

    /**
     * @return EntitySizeInfo
     */
    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1, 1);
    }

    /**
     * @return string
     */
    public static function getNetworkTypeId(): string {
        return EntityIds::PLAYER;
    }

    /**
     * @param int $id
     * @param Position $position
     *
     * @return MeteorBaseEntity
     * @throws AnimationException
     */
    public static function create(int $id, Position $position): MeteorBaseEntity {
        $entity = new MeteorBaseEntity(Location::fromObject($position, $position->getWorld()));
        $skin = Nexus::getInstance()->getDisplayManager()->getAnimationManager()->getSkin($id);
        if($skin === null) {
            throw new AnimationException("Unable to find the item skin of id \"$id\"");
        }
        $entity->setSkin($skin);
        $entity->setStart($position);
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
        if($this->designatedPosition === null or $this->speed === null) {
            return false;
        }
        $this->motion = new Vector3(0, 0, 0);
        $x = $this->designatedPosition->x - $this->pos->x;
        $y = $this->designatedPosition->y - $this->pos->y;
        $z = $this->designatedPosition->z - $this->pos->z;
        if($x * $x + $z * $z < 1.2) {
            $this->motion->x = 0;
            $this->motion->z = 0;
        }
        else {
            $this->motion->x = $this->speed * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->y = $this->speed * 0.15 * $y;
            $this->motion->z = $this->speed * 0.15 * ($z / (abs($x) + abs($z)));
        }
        if($y * $y > 0.6 and $this->motion->x === 0 and $this->motion->z === 0) {
            $this->motion->y = ($this->speed / 4) * 0.15 * $y;
        }
        elseif($y * $y < 0.6 and $this->motion->x === 0 and $this->motion->z === 0) {
            if($this->delay-- > 0) {
                return true;
            }
            if($this->hasFinishedAnimation() === false) {
                $this->setFinish(true);
                $callable = $this->onFinish;
                if($callable !== null) {
                    $callable($this);
                }
            }
        }
        else {
            $this->motion->x = $this->speed * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->y = $this->speed * 0.15 * ($y / (abs($x) + abs($z)));
            $this->motion->z = $this->speed * 0.15 * ($z / (abs($x) + abs($z)));
        }
        if($this->motion->x === 0 and $this->motion->z === 0 and $this->hasFinishedAnimation() and $this->scheduleDespawn) {
            $callable = $this->callable;
            if($callable !== null) {
                $callable($this);
            }
            $this->flagForDespawn();
            return false;
        }
        else {
            $this->location->yaw = ($this->location->yaw + 10) > 360 ? 0 : ($this->location->yaw + 10);
            $this->pos = $this->pos->add($this->motion->x, $this->motion->y, $this->motion->z);
            if(abs($this->originalPos->y - $this->designatedPosition->y) < 5) {
                $this->setPosition(new Vector3($this->pos->x, $this->designatedPosition->y, $this->pos->z));
                if($this->justCreated) {
                    $this->updateMovement(true);
                }
                else {
                    $this->updateMovement();
                }
            }
            else {
                $this->setPosition($this->pos);
                $this->updateMovement();
            }
        }
        parent::entityBaseTick($tickDiff);
        return $this->isAlive();
    }

    /**
     * @return bool
     */
    public function hasFinishedAnimation(): bool {
        return $this->finish;
    }

    /**
     * @param bool $finish
     */
    public function setFinish(bool $finish): void {
        $this->finish = $finish;
    }

    /**
     * @param Position|null $position
     * @param float $speed
     * @param bool $scheduleDespawn
     * @param int $delay
     * @param callable|null $callable
     * @param callable|null $onFinish
     */
    public function initialize(?Position $position = null, float $speed = 1.0, bool $scheduleDespawn = false, int $delay = 0, ?callable $callable = null, ?callable $onFinish = null) {
        $this->setFinish(false);
        $this->designatedPosition = $position;
        $this->speed = $speed * 2;
        $this->scheduleDespawn = $scheduleDespawn;
        $this->delay = $delay;
        $this->callable = $callable;
        $this->onFinish = $onFinish;
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
        return false;
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

    /**
     * @param Position $start
     */
    public function setStart(Position $start): void {
        $this->pos = $start;
        $this->originalPos = $start;
    }
    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);
        $this->uuid = Uuid::uuid4();
        $this->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.0);
        $this->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.0);
    }

    protected function applyGravity(): void {

    }

    protected function checkBlockCollision(): void {
    }

    /**
     * @param Player $player
     */
    protected function sendSpawnPacket(Player $player): void {
        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_ADD;
        $pk->entries = [PlayerListEntry::createAdditionEntry($this->uuid, $this->getId(), $this->getNameTag(), SkinAdapterSingleton::get()->toSkinData($this->getSkin()))];
        $player->getNetworkSession()->sendDataPacket($pk);
        $pk = new AddPlayerPacket();
        $pk->gameMode = 0;
        $pk->abilitiesPacket = UpdateAbilitiesPacket::create(new AbilitiesData(0, 0, $this->getId(), []));
        $pk->uuid = $this->getUniqueId();
        $pk->username = $this->getName();
        $pk->actorRuntimeId = $this->getId();
        $pk->position = $this->getPosition();
        $pk->motion = $this->getMotion();
        $pk->yaw = $this->location->yaw;
        $pk->pitch = $this->location->pitch;
        $pk->item = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(ItemFactory::getInstance()->get(ItemIds::AIR)));
        $pk->metadata = $this->getNetworkProperties()->getAll();
        $pk->syncedProperties = new PropertySyncData([], []);
        $player->getNetworkSession()->sendDataPacket($pk);
        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_REMOVE;
        $pk->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    /**
     * @return Skin
     */
    public function getSkin(): Skin {
        return $this->skin;
    }

    /**
     * @param Skin $skin
     */
    public function setSkin(Skin $skin): void {
        $this->skin = $skin;
    }

    /**
     * @param NexusPlayer $target
     */
    public function sendSkin(NexusPlayer $target): void{
        $pk = new PlayerSkinPacket();
        $pk->uuid = $this->getUniqueId();
        $pk->skin = SkinAdapterSingleton::get()->toSkinData($this->skin);
        $target->getNetworkSession()->sendDataPacket($pk);
    }

    /**
     * @return UuidInterface
     */
    public function getUniqueId(): UuidInterface {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->getNameTag();
    }
}