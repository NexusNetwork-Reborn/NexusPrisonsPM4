<?php

namespace core\display\animation\entity;

use core\display\animation\AnimationManager;
use core\game\kit\GodKit;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class TorchEntity extends Entity implements AnimationEntity {

    /** @var bool */
    public $canCollide = false;

    /** @var int */
    protected $gravity = 0;

    /** @var bool */
    protected $gravityEnabled = false;

    /** @var int */
    protected $drag = 0;

    /** @var null|float */
    protected $speed = null;

    /** @var int */
    protected $age = 0;

    /** @var UuidInterface */
    protected $uuid;

    /** @var Skin */
    protected $skin;

    /** @var Position */
    private $permanent;

    /** @var null */
    private $godKit = null;

    /** @var bool */
    private $fractured = true;

    /**
     * @return EntitySizeInfo
     */
    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(0.85, 0.01);
    }

    /**
     * @return string
     */
    public static function getNetworkTypeId(): string {
        return EntityIds::PLAYER;
    }

    /**
     * @param GodKit $kit
     * @param bool $fractured
     * @param Position $position
     *
     * @return TorchEntity
     */
    public static function create(GodKit $kit, bool $fractured, Position $position): TorchEntity {
        $entity = new TorchEntity(Location::fromObject($position, $position->getWorld()));
        $entity->setPermanent($position);
        $skin = Nexus::getInstance()->getDisplayManager()->getAnimationManager()->getSkin(ItemIds::REDSTONE_TORCH);
        $color = $kit->getColor();
        $name = $kit->getName();
        $customName = TextFormat::RESET . $color . TextFormat::BOLD . $name . TextFormat::RESET . $color . " G-Kit Flare";
        if($fractured) {
            $customName = TextFormat::RESET . $color . TextFormat::BOLD . $name . TextFormat::RESET . $color . " Fractured G-Kit Flare";
        }
        $entity->setGodKit($kit);
        $entity->setFractured($fractured);
        $entity->setNameTag($customName);
        $entity->setSkin($skin);
        $entity->setNameTagVisible();
        $entity->setNameTagAlwaysVisible();
        $entity->recalculateBoundingBox();
        $entity->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, $entity->getInitialSizeInfo()->getWidth());
        $entity->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, $entity->getInitialSizeInfo()->getHeight());
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
        if($this->godKit === null) {
            $this->flagForDespawn();
            return false;
        }
        $this->age += $tickDiff;
        if($this->age > 300) {
            $coords = [
                10,
                -10
            ];
            $entity = MeteorBaseEntity::create(AnimationManager::FLARE_ID, Position::fromObject($this->getPosition()->add($coords[mt_rand(0, 1)], 45, $coords[mt_rand(0, 1)]), $this->getWorld()));
            $entity->setScale(1.5);
            $position = Position::fromObject($this->permanent->subtract(0, 1, 0), $this->getWorld());
            $entity->initialize($position, 0.75, true, 0, function(MeteorBaseEntity $entity): void {
                $level = $entity->getWorld();
                if($level !== null) {
                    $bb = $this->getBoundingBox()->expandedCopy(12, 12, 12);
                    foreach($level->getNearbyEntities($bb) as $e) {
                        if($e instanceof NexusPlayer) {
                            $ev = new EntityDamageByEntityEvent($entity, $e, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, 4);
                            $e->attack($ev);
                        }
                    }
                    $flare = GKitEntity::create($this->getGodKit(), $this->isFractured(), $this->permanent);
                    $flare->spawnToAll();
                    $level->addParticle($entity->getPosition(), new HugeExplodeSeedParticle());
                    $level->addSound($entity->getPosition(), new ExplodeSound());
                }
            });
            $entity->spawnToAll();
            $this->flagForDespawn();
            return false;
        }
        $this->setScoreTag(TextFormat::RESET . TextFormat::WHITE . number_format((300 - $this->age) / 20, 1) . "s");
        $this->location->yaw = ($this->location->yaw + 10) > 360 ? 0 : ($this->location->yaw + 10);
        if($this->permanent !== null) {
            $this->setPosition($this->permanent);
        }
        $this->updateMovement();
        return $this->isAlive() and parent::entityBaseTick($tickDiff);
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
     * @param Position $permanent
     */
    public function setPermanent(Position $permanent): void {
        $this->permanent = $permanent;
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

    /**
     * @param bool $fractured
     */
    public function setFractured(bool $fractured): void {
        $this->fractured = $fractured;
    }

    /**
     * @return bool
     */
    public function isFractured(): bool {
        return $this->fractured;
    }

    /**
     * @param null $godKit
     */
    public function setGodKit($godKit): void {
        $this->godKit = $godKit;
    }

    /**
     * @return null
     */
    public function getGodKit() {
        return $this->godKit;
    }
}