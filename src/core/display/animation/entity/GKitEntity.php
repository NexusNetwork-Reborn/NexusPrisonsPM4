<?php

namespace core\display\animation\entity;

use core\display\animation\AnimationException;
use core\display\animation\AnimationManager;
use core\game\item\types\custom\GKitBeacon;
use core\game\kit\GodKit;
use core\Nexus;
use core\player\NexusPlayer;
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
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\EnchantmentTableParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\PopSound;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class GKitEntity extends Entity implements AnimationEntity {

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

    /** @var null|GodKit */
    private $godKit = null;

    /** @var bool */
    private $fractured = true;

    /** @var bool */
    private $examining = false;

    /** @var int */
    private $time = 0;

    /** @var int */
    private $i = 0;

    /**
     * @return EntitySizeInfo
     */
    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1.5, 1);
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
     * @return GKitEntity
     * @throws AnimationException
     */
    public static function create(GodKit $kit, bool $fractured, Position $position): GKitEntity {
        $entity = new GKitEntity(Location::fromObject($position, $position->getWorld()));
        $entity->setPermanent($position);
        $skin = Nexus::getInstance()->getDisplayManager()->getAnimationManager()->getSkin(AnimationManager::FLARE_ID);
        $color = $kit->getColor();
        $name = $kit->getName();
        $customName = TextFormat::RESET . $color . TextFormat::BOLD . $name . TextFormat::RESET . $color . " G-Kit Flare";
        if($fractured) {
            $customName = TextFormat::RESET . $color . TextFormat::BOLD . $name . TextFormat::RESET . $color . " Fractured G-Kit Flare";
        }
        $entity->setScale(0.75);
        $entity->setImmobile();
        $entity->setGodKit($kit);
        $entity->setFractured($fractured);
        $entity->setNameTag($customName);
        $entity->setScoreTag(TextFormat::RESET . TextFormat::GRAY . "Click to examine");
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
        $this->age++;
        $level = $this->getWorld();
        if($level !== null) {
            $active = $this->getOwningEntity() !== null ? $this->getOwningEntity()->getPosition()->distance($this->getPosition()) <= 8 : false;
            if($active and $this->examining) {
                $this->time += $tickDiff;
                if($this->time >= 600) {
                    $level->addParticle($this->getPosition(), new BlockBreakParticle(VanillaBlocks::BEDROCK()));
                    $level->addSound($this->getPosition(), new PopSound());
                    $owner = $this->getOwningEntity();
                    if($owner instanceof NexusPlayer) {
                        $chance = 30;
                        if($this->fractured) {
                            $chance /= 2;
                        }
                        $color = $this->godKit->getColor();
                        $name = $this->godKit->getName();
                        $customName = TextFormat::RESET . $color . TextFormat::BOLD . $name . TextFormat::BOLD . $color . " G-Kit Flare" . TextFormat::RESET;
                        if($this->fractured) {
                            $customName = TextFormat::RESET . $color . TextFormat::BOLD . $name . TextFormat::BOLD . $color . " Fractured G-Kit Flare" . TextFormat::RESET;
                        }
                        $owner->getServer()->broadcastMessage(TextFormat::WHITE . $owner->getName() . TextFormat::GOLD . " has examined a $customName");
                        if($chance >= mt_rand(1, 100)) {
                            $item = (new GKitBeacon($this->godKit))->toItem();
                            $inventory = $owner->getInventory();
                            if($inventory->canAddItem($item)) {
                                $inventory->addItem($item);
                            }
                            else {
                                $level->dropItem($this->getPosition(), $item);
                            }
                            $owner->getServer()->broadcastMessage(TextFormat::WHITE . $owner->getName() . TextFormat::GOLD . " has received permanent access to " . $item->getCustomName());
                        }
                        else {
                            $this->godKit->giveTo($owner);
                        }
                    }
                    $this->flagForDespawn();
                    return false;
                }
                $this->setScoreTag(TextFormat::RESET . TextFormat::WHITE . "Examining... (" . number_format((600 - $this->time) / 20, 1) . "s)");
                $this->location->yaw = ($this->location->yaw + 36) > 360 ? 0 : ($this->location->yaw + 36);
                if(($this->age % 5) == 0) {
                    $radio = 1;
                    for($i = 0; $i <= 1; $i++) {
                        $this->i += 0.2;
                        if($this->i > 3) {
                            $this->i = 0;
                        }
                        $x = $radio * sin($this->i);
                        $z = $radio * cos($this->i);
                        $particle = new EnchantmentTableParticle();
                        $level->addParticle(new Vector3($this->getPosition()->x + $x, $this->getPosition()->y + ($this->i - 1), $this->getPosition()->z + $z), $particle);
                        $x = -$radio * sin($this->i);
                        $z = -$radio * cos($this->i);
                        $particle = new EnchantmentTableParticle();
                        $level->addParticle(new Vector3($this->getPosition()->x + $x, $this->getPosition()->y + ($this->i - 1), $this->getPosition()->z + $z), $particle);
                    }
                }
            }
            elseif($this->examining and (!$active)) {
                $owner = $this->getOwningEntity();
                if($owner instanceof NexusPlayer) {
                    $owner->sendMessage(TextFormat::RED . "You left the examination site! Your G-Kit went inactive.");
                }
                $this->setScoreTag(TextFormat::RESET . TextFormat::GRAY . "Click to examine");
                $this->examining = false;
                $this->time = 0;
                $this->location->yaw = 0;
            }
            if($this->permanent !== null) {
                $this->setPosition($this->permanent);
            }
            $this->updateMovement();
        }
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
        if($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if($damager instanceof NexusPlayer) {
                if(!$this->examining) {
                    $this->examining = true;
                    $this->setOwningEntity($damager);
                }
            }
        }
        $source->cancel();
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
    }

    protected function applyGravity(): void {

    }

    protected function checkBlockCollision(): void {
    }

    /**
     * @param Player $player
     */
    protected function sendSpawnPacket(Player $player): void {
        /** @var TypeConverter $converter */
        $converter = TypeConverter::getInstance();
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
        $pk->item = ItemStackWrapper::legacy($converter->coreItemStackToNet(ItemFactory::getInstance()->get(ItemIds::AIR)));
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
     * @return GodKit|null
     */
    public function getGodKit(): ?GodKit {
        return $this->godKit;
    }

    /**
     * @param GodKit|null $godKit
     */
    public function setGodKit(?GodKit $godKit): void {
        $this->godKit = $godKit;
    }

    /**
     * @return bool
     */
    public function isFractured(): bool {
        return $this->fractured;
    }

    /**
     * @param bool $fractured
     */
    public function setFractured(bool $fractured): void {
        $this->fractured = $fractured;
    }
}