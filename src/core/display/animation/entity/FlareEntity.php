<?php

namespace core\display\animation\entity;

use core\display\animation\AnimationManager;
use core\game\kit\GodKit;
use core\level\FakeChunkLoader;
use core\level\tile\Meteor;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\LevelUtils;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\StainedGlass;
use pocketmine\block\utils\CoralType;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
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
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\ChunkLoader;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\World;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class FlareEntity extends Entity implements AnimationEntity {

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

    /** @var Position[] */
    private $blocks = [];

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

    /** @var ChunkLoader */
    private $tempLoader = null;

    /**
     * @param Position $position
     * @param array $blocks
     *
     * @return FlareEntity
     */
    public static function create(Position $position, array $blocks): FlareEntity {
        $entity = new FlareEntity(Location::fromObject($position, $position->getWorld()));
        $entity->setPermanent($position);
        $skin = Nexus::getInstance()->getDisplayManager()->getAnimationManager()->getSkin(ItemIds::REDSTONE_TORCH);
        $customName = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Meteor";
        $entity->setBlocks($blocks);
        $entity->setNameTag($customName);
        $entity->setSkin($skin);
        $entity->setNameTagVisible();
        $entity->setNameTagAlwaysVisible();
        $entity->recalculateBoundingBox();
        $entity->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, $entity->getInitialSizeInfo()->getWidth());
        $entity->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, $entity->getInitialSizeInfo()->getHeight());
        $x = $position->getFloorX() >> 4;
        $z = $position->getFloorZ() >> 4;
        $entity->getWorld()->registerChunkLoader($tL = new FakeChunkLoader($x, $z), $x, $z);
        $entity->tempLoader = $tL;
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
        $this->age += $tickDiff;
        if($this->age > 3600) {
            $coords = [
                10,
                -10
            ];
            $entity = MeteorBaseEntity::create(AnimationManager::METEOR_ID, Position::fromObject($this->getPosition()->add($coords[mt_rand(0, 1)], 45, $coords[mt_rand(0, 1)]), $this->getWorld()));
            $entity->setScale(3.0);
            $entity->initialize($this->permanent, 0.15, true, 0, function(MeteorBaseEntity $entity): void {
                $world = $entity->getWorld();
                if($world !== null) {
                    $bb = $this->getBoundingBox()->expandedCopy(12, 12, 12);
                    foreach($world->getNearbyEntities($bb) as $e) {
                        if($e instanceof NexusPlayer) {
                            $ev = new EntityDamageByEntityEvent($entity, $e, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, 4);
                            $e->attack($ev);
                        }
                    }
                    $world->addParticle($entity->getPosition(), new HugeExplodeSeedParticle());
                    $world->addSound($entity->getPosition(), new ExplodeSound());
                    foreach($this->blocks as $block) {
                        if($world->getBlock($block)->getId() == BlockLegacyIds::AIR) {
                            $coral = VanillaBlocks::CORAL_BLOCK()->setCoralType(CoralType::BRAIN())->setDead(true);
                            $world->setBlock($block, $coral);
                            $tile = new Meteor($world, $block);
                            $world->addTile($tile);
                        }
                    }
                }
            });
            $entity->spawnToAll();
            $this->flagForDespawn();
            if(($tL = $this->tempLoader) instanceof ChunkLoader) {
                $this->getWorld()->unregisterChunkLoader($tL, ((int) floor($tL->getX())) >> 4, ((int) floor($tL->getZ())) >> 4);
            }
            return false;
        }
        $this->setScoreTag(TextFormat::RESET . TextFormat::WHITE . number_format((3600 - $this->age) / 20, 1) . "s");
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
     * @param Position[] $blocks
     */
    public function setBlocks(array $blocks): void {
        $this->blocks = $blocks;
    }
}