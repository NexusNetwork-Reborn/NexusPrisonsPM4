<?php

namespace core\display\animation\entity;

use core\display\animation\AnimationException;
use core\display\animation\AnimationManager;
use core\game\rewards\Reward;
use core\game\rewards\types\MonthlyRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\sound\FizzSound;
use pocketmine\world\sound\TotemUseSound;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CrateEntity extends Entity implements AnimationEntity {

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

    /** @var int */
    protected $age = 0;

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

    /** @var CrystalEntity[] */
    protected $crystals = [];

    /** @var MonthlyRewards */
    protected $rewards;

    /** @var Reward[] */
    protected $allRewards;

    /** @var Item[] */
    protected $roll = [];

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
     * @param NexusPlayer $player
     * @param MonthlyRewards $rewards
     * @param Position $position
     *
     * @return static
     * @throws AnimationException
     */
    public static function create(NexusPlayer $player, MonthlyRewards $rewards, Position $position): self {
        $entity = new CrateEntity(Location::fromObject($position, $position->getWorld()));
        $skin = Nexus::getInstance()->getDisplayManager()->getAnimationManager()->getSkin(AnimationManager::SPACE_CHEST_ID);
        if($skin === null) {
            throw new AnimationException("Unable to find the item skin of id \"-3\"");
        }
        $entity->setOwningEntity($player);
        $entity->setScale(2.0);
        $entity->setSkin($skin);
        $entity->setStart($position);
        $entity->setRewards($rewards);
        $position->getWorld()->addSound($position, new TotemUseSound());
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
        $owner = $this->getOwningEntity();
        if(!$owner instanceof NexusPlayer) {
            if(!$this->isFlaggedForDespawn()) {
                foreach($this->roll as $item) {
                    for($i = $item->getCount(); $i > 64; $i -= 64) {
                        $this->getWorld()->dropItem($this->getPosition(), $item->setCount(64));
                    }
                    $this->getWorld()->dropItem($this->getPosition(), $item->setCount($i));
                }
            }
            $this->flagForDespawn();
            foreach($this->crystals as $crystal) {
                if(!$crystal->isFlaggedForDespawn() and !$crystal->isClosed()) {
                    $crystal->flagForDespawn();
                }
            }
            return false;
        }
        if($this->age++ > 2000) {
            $this->setFinish(true);
        }
        $position = $this->pos;
        if($this->finish) {
            if($this->age === 100) {
                $reward = $this->rewards->rollBonus()->executeCallback($owner);
                $this->roll[] = $reward;
                $name = $reward->hasCustomName() ? $reward->getCustomName() : $reward->getName();
                $name = $name . TextFormat::RESET . TextFormat::GRAY . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . $reward->getCount();
                $entity = CrystalEntity::create(Position::fromObject($this->pos->subtract(0, 1, 0), $this->getWorld()));
                $entity->setNameTag($name);
                $entity->setNameTagAlwaysVisible();
                $this->playImpactSound();
                $this->spawnStrike();
                $entity->initialize(Position::fromObject($position->add(0, 7, 0), $this->getWorld()), 1);
                $entity->spawnToAll();
                $this->crystals[] = $entity;
            }
            if($this->age === 200) {
                foreach($this->crystals as $crystal) {
                    if((!$crystal->isFlaggedForDespawn()) and (!$crystal->isClosed())) {
                        $crystal->flagForDespawn();
                    }
                }
                foreach($this->roll as $item) {
                    if($owner->getInventory()->canAddItem($item)) {
                        $owner->getInventory()->addItem($item);
                    }
                    else {
                        for($i = $item->getCount(); $i > 64; $i -= 64) {
                            $owner->getWorld()->dropItem($owner->getPosition(), $item->setCount(64));
                        }
                        $owner->getWorld()->dropItem($owner->getPosition(), $item->setCount($i));
                    }
                }
                $this->getWorld()->addParticle($this->pos, new HugeExplodeParticle());
                $this->getWorld()->addSound($this->pos, new ExplodeSound());
                $this->flagForDespawn();
                return false;
            }
            elseif($this->age <= 95) {
                $speed = (int)min(30, ceil($this->age / 2));
                if($this->age % 2 === 0) {
                    $this->getWorld()->addSound($this->pos, new FizzSound());
                }
                $this->location->yaw = ($this->location->yaw + $speed) > 360 ? 0 : ($this->location->yaw + $speed);
                $this->setPosition($this->originalPos->add(0, 2, 0));
                $this->updateMovement();
                return true;
            }
            else {
                return true;
            }
        }
        $i = $this->age % 40;
        $pi = 3.14159;
        $total = count($this->rewards->getAllRewards());
        $radius = $total / 2;
        $radius = $radius + 0.1 / $pi;
        if($i === 0) {
            $i = ($this->age / 40) * ($pi / ($total / 2));
            if($i <= 2 * $pi) {
                $reward = array_shift($this->allRewards)->executeCallback($owner);
                $this->roll[] = $reward;
                $x = $radius * cos($i);
                $z = $radius * sin($i);
                $pos = $position->add($x, 4.5, $z);
                $entity = CrystalEntity::create($this->pos);
                $entity->initialize(Position::fromObject($pos, $this->getWorld()), 3);
                $name = $reward->hasCustomName() ? $reward->getCustomName() : $reward->getName();
                $name = $name . TextFormat::RESET . TextFormat::GRAY . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . $reward->getCount();
                $entity->setNameTag($name);
                $entity->setNameTagAlwaysVisible();
                $entity->spawnToAll();
                $this->crystals[] = $entity;
                $this->getWorld()->addSound($this->pos, new BlazeShootSound());
            }
            else {
                $this->setFinish(true);
                $this->age = 0;
            }
        }
        parent::entityBaseTick($tickDiff);
        return $this->isAlive();
    }

    public function playImpactSound(): void {
        $spk = new PlaySoundPacket();
        $spk->soundName = "ambient.weather.lightning.impact";
        $spk->x = $this->getPosition()->getX();
        $spk->y = $this->getPosition()->getY();
        $spk->z = $this->getPosition()->getZ();
        $spk->volume = 500;
        $spk->pitch = 1;
        foreach($this->getViewers() as $p) {
            $p->getNetworkSession()->sendDataPacket($spk);
        }
    }

    public function spawnStrike(): void {
        $pk = new AddActorPacket();
        $pk->actorRuntimeId = Entity::nextRuntimeId();
        $pk->actorUniqueId = $pk->actorRuntimeId;
        $pk->type = EntityIds::LIGHTNING_BOLT;
        $pk->position = $this->getPosition();
        $pk->motion = new Vector3(0, 0, 0);
        $pk->yaw = $this->getLocation()->getYaw();
        $pk->headYaw = $this->getLocation()->getYaw();
        $pk->pitch = $this->getLocation()->getPitch();
        $pk->attributes = $this->attributeMap->getAll();
        $pk->metadata = $this->getAllNetworkData();
        $pk->syncedProperties = new PropertySyncData([], []);
        foreach($this->getViewers() as $player) {
            $player->getNetworkSession()->sendDataPacket($pk);
        }
    }

    /**
     * @param MonthlyRewards $rewards
     */
    public function setRewards(MonthlyRewards $rewards): void {
        $this->rewards = $rewards;
        $this->allRewards = $rewards->getAllRewards();
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
        $this->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0);
        $this->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0);
    }

    /**
     * @param EntityMetadataCollection $properties
     */
    protected function syncNetworkData(EntityMetadataCollection $properties): void {
        parent::syncNetworkData($properties);
        $properties->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0);
        $properties->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0);
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