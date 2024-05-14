<?php

namespace core\display\animation\entity;

use core\Nexus;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use pocketmine\uuid\UUID;
use Ramsey\Uuid\UuidInterface;

class TestEntity extends Living implements AnimationEntity {

    public const NETWORK_ID = self::PLAYER;

    /** @var float */
    public $width = 2.0;

    /** @var float */
    public $height = 2.0;

    /** @var bool */
    public $canCollide = false;

    /** @var int */
    protected $gravity = 0;

    /** @var int */
    protected $drag = 0;

    /** @var UUID */
    protected $uuid;

    /** @var int */
    protected $age = 0;

    /**
     * @param int $tickDiff
     *
     * @return bool
     */
    protected function entityBaseTick(int $tickDiff = 1): bool {
        if($this->age++ > 300) {
            $this->flagForDespawn();
        }
        elseif($this->age <= 150) {
            $this->yaw = ($this->yaw + 10) > 360 ? 0 : ($this->yaw + 10);
        }
        else {
            $this->pitch = 60;
            $this->yaw = ($this->yaw + 10) > 360 ? 0 : ($this->yaw + 10);
        }
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

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);
        $this->uuid = UUID::fromRandom();
        $this->setNameTag("nametag");
        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();
        $this->getDataPropertyManager()->setFloat(self::DATA_BOUNDING_BOX_WIDTH, 0.0);
        $this->getDataPropertyManager()->setFloat(self::DATA_BOUNDING_BOX_HEIGHT, 0.0);
    }

    protected function applyGravity(): void {

    }

    /**
     * @return bool
     */
    protected function applyDragBeforeGravity(): bool {
        return true;
    }

    protected function checkBlockCollision(): void {
    }

    /**
     * @param Player $player
     */
    protected function sendSpawnPacket(Player $player): void {
        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_ADD;
        $pk->entries = [PlayerListEntry::createAdditionEntry($this->getUniqueId(), $this->getId(), $this->getNameTag(), SkinAdapterSingleton::get()->toSkinData($this->getSkin()))];
        $player->getNetworkSession()->sendDataPacket($pk);
        $pk = new AddPlayerPacket();
        $pk->gameMode = 0;
        $pk->abilitiesPacket = UpdateAbilitiesPacket::create(new AbilitiesData(0, 0, $this->getId(), []));
        $pk->uuid = $this->getUniqueId();
        $pk->username = $this->getName();
        $pk->actorRuntimeId = $this->getId();
        $pk->position = $this->getPosition()->asVector3();
        $pk->motion = $this->getMotion();
        $pk->yaw = $this->getLocation()->getYaw();
        $pk->pitch = $this->getLocation()->getPitch();
        $pk->item = ItemStackWrapper::legacy(new ItemStack(0, 9, 0, 0, null, [], []));
        $pk->metadata = $this->getNetworkProperties()->getAll();
        $pk->syncedProperties = new PropertySyncData([], []);
        $player->getNetworkSession()->sendDataPacket($pk);
        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_REMOVE;
        $pk->entries = [PlayerListEntry::createRemovalEntry($this->getUniqueId())];
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    /**
     * @return Skin
     */
    public function getSkin(): Skin {
        return Nexus::getInstance()->getDisplayManager()->getAnimationManager()->getSkin(VanillaItems::DIAMOND_PICKAXE()->getId());
    }

    /**
     * @return UuidInterface
     */
    public function getUniqueId(): UuidInterface {
        return $this->getUniqueId();
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->getNameTag();
    }
}