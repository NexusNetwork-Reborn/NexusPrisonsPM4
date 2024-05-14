<?php

namespace core\display\animation\entity;

use core\display\animation\AnimationException;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rpg\XPUtils;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
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
use pocketmine\world\Position;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class LevelUpEntity extends Entity implements AnimationEntity {

    /** @var bool */
    public $canCollide = false;

    /** @var int */
    protected $gravity = 0;

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

    /**
     * @return EntitySizeInfo
     */
    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(2, 2);
    }

    /**
     * @return string
     */
    public static function getNetworkTypeId(): string {
        return EntityIds::PLAYER;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return LevelUpEntity
     * @throws AnimationException
     */
    public static function create(NexusPlayer $player): LevelUpEntity {
        $directionVector = $player->getDirectionVector()->multiply(2);
        $position = Position::fromObject($player->getPosition()->add($directionVector->x, $directionVector->y, $directionVector->z), $player->getWorld());
        $entity = new LevelUpEntity(Location::fromObject($position, $position->getWorld()));
        $entity->setOwningEntity($player);
        $level = XPUtils::xpToLevel($player->getDataSession()->getXP());
        if($level >= 90) {
            $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE);
        }
        elseif($level >= 70) {
            $item = ItemFactory::getInstance()->get(ItemIds::IRON_PICKAXE);
        }
        elseif($level >= 50) {
            $item = ItemFactory::getInstance()->get(ItemIds::GOLD_PICKAXE);
        }
        elseif($level >= 30) {
            $item = ItemFactory::getInstance()->get(ItemIds::STONE_PICKAXE);
        }
        else {
            $item = ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE);
        }
        $skin = Nexus::getInstance()->getDisplayManager()->getAnimationManager()->getSkin($item->getId());
        if($skin === null) {
            throw new AnimationException("Unable to find the item skin of id \"{$item->getId()}\"");
        }
        $entity->setSkin($skin);
        $entity->setNameTag(TextFormat::BOLD . TextFormat::GOLD . "LEVEL UP");
        $entity->setNameTagVisible();
        $entity->setNameTagAlwaysVisible();
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
        if($this->age++ > 200) {
            $this->flagForDespawn();
            return false;
        }
        if($this->getOwningEntity() === null) {
            $this->flagForDespawn();
            return false;
        }
        $player = $this->getOwningEntity();
        $directionVector = $player->getDirectionVector()->multiply(3);
        $position = Position::fromObject($player->getPosition()->add($directionVector->x, $directionVector->y + $player->getEyeHeight() + 1, $directionVector->z), $player->getWorld());
        $this->location->yaw = ($this->location->yaw + 10) > 360 ? 0 : ($this->location->yaw + 10);
        $this->setPosition($position);
        $this->updateMovement();
        return $this->isAlive();
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
     * @param CompoundTag $nbt
     */
    protected function initEntity(CompoundTag $nbt): void {
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
}