<?php
declare(strict_types=1);

namespace core\game\combat\guards;

use core\game\combat\guards\types\Enforcer;
use core\game\combat\guards\types\Safeguard;
use core\game\combat\guards\types\Warden;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Utils;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector2;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Guard {

    /** @var string */
    private $class;

    /** @var string */
    private $nameTag;

    /** @var Skin */
    private $skin;

    /** @var int */
    private $entityId;

    /** @var UuidInterface */
    private $uuid;

    /** @var Position */
    private $station;

    /** @var AxisAlignedBB */
    private $boundingBox;

    /** @var NexusPlayer[] */
    private $spawned = [];

    /**
     * Safeguard constructor.
     *
     * @param string $class
     * @param Position $station
     */
    public function __construct(string $class, Position $station) {
        $this->class = $class;
        $this->station = $station;
        $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR;
        switch($class) {
            case Enforcer::class:
                $path .= "enforcer.png";
                $skin = Utils::createSkin(Utils::getSkinDataFromPNG($path));
                $nameTag = TextFormat::BOLD . TextFormat::YELLOW . "Enforcer";
                break;
            case Safeguard::class:
                $path .= "guard.png";
                $skin = Utils::createSkin(Utils::getSkinDataFromPNG($path));
                $nameTag = TextFormat::BOLD . TextFormat::AQUA . "Guard";
                break;
            default:
                $path .= "warden.png";
                $skin = Utils::createSkin(Utils::getSkinDataFromPNG($path));
                $nameTag = TextFormat::BOLD . TextFormat::RED . "Warden";
                break;
        }
        $this->skin = $skin;
        $this->nameTag = $nameTag;
        $this->entityId = Entity::nextRuntimeId();
        $this->uuid = Uuid::uuid4();
        $halfWidth = 0.6 / 2;
        $this->boundingBox = new AxisAlignedBB($station->x - $halfWidth, $station->y, $station->z - $halfWidth, $station->x + $halfWidth, $station->y + 1.8, $station->z + $halfWidth);
    }

    /**
     * @return string
     */
    public function getClass(): string {
        return $this->class;
    }

    /**
     * @return AxisAlignedBB
     */
    public function getBoundingBox(): AxisAlignedBB {
        return $this->boundingBox;
    }

    /**
     * @param NexusPlayer $player
     */
    public function spawnTo(NexusPlayer $player): void {
        $this->spawned[$player->getUniqueId()->toString()] = $player;
        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_ADD;
        $pk->entries = [PlayerListEntry::createAdditionEntry($this->uuid, $this->entityId, $this->getNameTag(), SkinAdapterSingleton::get()->toSkinData($this->skin))];
        $player->getNetworkSession()->sendDataPacket($pk);
        $xdiff = $player->getPosition()->x - $this->station->x;
        $zdiff = $player->getPosition()->z - $this->station->z;
        $angle = atan2($zdiff, $xdiff);
        $yaw = (($angle * 180) / M_PI) - 90;
        $ydiff = $player->getPosition()->y - $this->station->y;
        $v = new Vector2($this->station->x, $this->station->z);
        $dist = $v->distance(new Vector2($player->getPosition()->x, $player->getPosition()->z));
        $angle = atan2($dist, $ydiff);
        $pitch = (($angle * 180) / M_PI) - 90;
        $pk = new AddPlayerPacket();
        $pk->gameMode = 0;
        $pk->abilitiesPacket = UpdateAbilitiesPacket::create(new AbilitiesData(0, 0, $this->entityId, []));
        $pk->uuid = $this->getUniqueId();
        $pk->username = $this->nameTag;
        $pk->actorRuntimeId = $this->entityId;
        $pk->position = $this->station->asVector3();
        $pk->yaw = $yaw;
        $pk->pitch = $pitch;
        $pk->item = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(ItemFactory::getInstance()->get(ItemIds::AIR)));
        $pk->syncedProperties = new PropertySyncData([], []);
        switch($this->class) {
            case Enforcer::class:
                $hp = 250;
                break;
            case Safeguard::class:
                $hp = 100;
                break;
            default:
                $hp = 1000;
                break;
        }
        $flags = (
            1 << EntityMetadataFlags::ALWAYS_SHOW_NAMETAG
            ^ 1 << EntityMetadataFlags::CAN_SHOW_NAMETAG
        );
        $pk->metadata = [
            EntityMetadataProperties::FLAGS => new LongMetadataProperty($flags),
            EntityMetadataProperties::NAMETAG => new StringMetadataProperty($this->nameTag),
            EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty(TextFormat::WHITE . $hp . TextFormat::BOLD . TextFormat::RED . " HP"),
            EntityMetadataProperties::LEAD_HOLDER_EID => new LongMetadataProperty(-1)
        ];
        $player->getNetworkSession()->sendDataPacket($pk);
        $this->setNameTag($player);
        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_REMOVE;
        $pk->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
        $player->getNetworkSession()->sendDataPacket($pk);
        switch($this->class) {
            case Enforcer::class:
                $pk = new MobEquipmentPacket();
                $pk->actorRuntimeId = $this->entityId;
                $pk->inventorySlot = $pk->hotbarSlot = 0;
                $ench = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1);
                $item = VanillaItems::DIAMOND_SWORD();
                $item->addEnchantment($ench);
                $pk->item = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($item));
                $player->getNetworkSession()->sendDataPacket($pk);
                $pk = new MobArmorEquipmentPacket();
                $pk->actorRuntimeId = $this->entityId;
                $pk->head = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(ItemFactory::getInstance()->get(ItemIds::AIR)));
                $chest = VanillaItems::GOLDEN_CHESTPLATE();
                $chest->addEnchantment($ench);
                $pk->chest = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($chest));
                $legs = VanillaItems::GOLDEN_LEGGINGS();
                $legs->addEnchantment($ench);
                $pk->legs = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($legs));
                $feet = VanillaItems::GOLDEN_BOOTS();
                $feet->addEnchantment($ench);
                $pk->feet = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($feet));
                $player->getNetworkSession()->sendDataPacket($pk);
                break;
            case Safeguard::class:
                $pk = new MobEquipmentPacket();
                $pk->actorRuntimeId = $this->entityId;
                $pk->inventorySlot = $pk->hotbarSlot = 0;
                $pk->item = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(VanillaItems::DIAMOND_SWORD()));
                $player->getNetworkSession()->sendDataPacket($pk);
                $pk = new MobArmorEquipmentPacket();
                $pk->actorRuntimeId = $this->entityId;
                $pk->head = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(ItemFactory::getInstance()->get(ItemIds::AIR)));
                $pk->chest = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(VanillaItems::GOLDEN_CHESTPLATE()));
                $pk->legs = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(VanillaItems::GOLDEN_LEGGINGS()));
                $pk->feet = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(VanillaItems::GOLDEN_BOOTS()));
                $player->getNetworkSession()->sendDataPacket($pk);
                break;
            case Warden::class:
                $pk = new MobEquipmentPacket();
                $pk->actorRuntimeId = $this->entityId;
                $pk->inventorySlot = $pk->hotbarSlot = 0;
                $ench = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1);
                $item = VanillaItems::DIAMOND_AXE();
                $item->addEnchantment($ench);
                $pk->item = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($item));
                $player->getNetworkSession()->sendDataPacket($pk);
                $pk = new MobArmorEquipmentPacket();
                $pk->actorRuntimeId = $this->entityId;
                $pk->head = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(ItemFactory::getInstance()->get(ItemIds::AIR)));
                $chest = VanillaItems::DIAMOND_CHESTPLATE();
                $chest->addEnchantment($ench);
                $pk->chest = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($chest));
                $legs = VanillaItems::DIAMOND_LEGGINGS();
                $legs->addEnchantment($ench);
                $pk->legs = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($legs));
                $feet = VanillaItems::DIAMOND_BOOTS();
                $feet->addEnchantment($ench);
                $pk->feet = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($feet));
                $player->getNetworkSession()->sendDataPacket($pk);
                break;
            default:
                return;
        }
    }

    /**
     * @param NexusPlayer $target
     */
    public function spawnRealTo(NexusPlayer $target): void {
        $level = $this->station->getWorld();
        /** @var BaseGuard $entity */
        $entity = new $this->class(Location::fromObject($this->station, $level));
        $entity->setTarget($target);
        $entity->spawnToAll();
    }

    /**
     * @return string
     */
    public function getNameTag(): string {
        return $this->nameTag;
    }

    /**
     * @param NexusPlayer $player
     */
    public function setNameTag(NexusPlayer $player): void {
        $pk = SetActorDataPacket::create(
            $this->entityId,
            [EntityMetadataProperties::NAMETAG => new StringMetadataProperty($this->nameTag)],
            new PropertySyncData([], []),
            0,
        );
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    /**
     * @return UuidInterface
     */
    public function getUniqueId(): UuidInterface {
        return $this->uuid;
    }

    /**
     * @param NexusPlayer $player
     */
    public function despawnFrom(NexusPlayer $player): void {
        unset($this->spawned[$player->getUniqueId()->toString()]);
        $pk = new RemoveActorPacket();
        $pk->actorUniqueId = $this->entityId;
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function despawnFromAll(): void {
        foreach($this->spawned as $player) {
            $this->despawnFrom($player);
        }
    }

    /**
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public function isSpawned(NexusPlayer $player): bool {
        return isset($this->spawned[$player->getUniqueId()->toString()]);
    }

    /**
     * @param NexusPlayer $player
     */
    public function move(NexusPlayer $player): void {
        $xdiff = $player->getPosition()->x - $this->station->x;
        $zdiff = $player->getPosition()->z - $this->station->z;
        $angle = atan2($zdiff, $xdiff);
        $yaw = (($angle * 180) / M_PI) - 90;
        $ydiff = $player->getPosition()->y - $this->station->y;
        $v = new Vector2($this->station->x, $this->station->z);
        $dist = $v->distance(new Vector2($player->getPosition()->x, $player->getPosition()->z));
        $angle = atan2($dist, $ydiff);
        $pitch = (($angle * 180) / M_PI) - 90;
        $pk = new MovePlayerPacket();
        $pk->actorRuntimeId = $this->entityId;
        $pk->position = $this->station->asVector3()->add(0, 1.62, 0);
        $pk->yaw = $yaw;
        $pk->pitch = $pitch;
        $pk->headYaw = $yaw;
        $pk->onGround = true;
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    /**
     * @return Skin
     */
    public function getSkin(): Skin {
        return $this->skin;
    }

    /**
     * @return int
     */
    public function getEntityId(): int {
        return $this->entityId;
    }

    /**
     * @return Position
     */
    public function getStation(): Position {
        return $this->station;
    }
}
