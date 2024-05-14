<?php
declare(strict_types=1);

namespace core\level\entity\npc;

use core\player\NexusPlayer;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector2;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\world\Position;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class NPC {

    /** @var string */
    private $nameTag;

    /** @var Skin */
    private $skin;

    /** @var callable */
    private $callable;

    /** @var int */
    private $entityId;

    /** @var UuidInterface */
    private $uuid;

    /** @var Position */
    private $position;

    /** @var NexusPlayer[] */
    private $spawned = [];

    /**
     * NPC constructor.
     *
     * @param Skin $skin
     * @param Position $position
     * @param string $nameTag
     * @param callable $callable
     */
    public function __construct(Skin $skin, Position $position, string $nameTag, callable $callable) {
        $this->skin = $skin;
        $this->position = $position;
        $this->nameTag = $nameTag;
        $this->callable = $callable;
        $this->entityId = Entity::nextRuntimeId();
        $this->uuid = Uuid::uuid4();
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
        $xdiff = $player->getPosition()->x - $this->position->x;
        $zdiff = $player->getPosition()->z - $this->position->z;
        $angle = atan2($zdiff, $xdiff);
        $yaw = (($angle * 180) / M_PI) - 90;
        $ydiff = $player->getPosition()->y - $this->position->y;
        $v = new Vector2($this->position->x, $this->position->z);
        $dist = $v->distance(new Vector2($player->getPosition()->x, $player->getPosition()->z));
        $angle = atan2($dist, $ydiff);
        $pitch = (($angle * 180) / M_PI) - 90;
        $pk = new AddPlayerPacket();
        $pk->gameMode = 0;
        $pk->abilitiesPacket = UpdateAbilitiesPacket::create(new AbilitiesData(0, 0, $this->getEntityId(), []));
        $pk->uuid = $this->getUniqueId();
        $pk->username = $this->nameTag;
        $pk->actorRuntimeId = $this->entityId;
        $pk->position = $this->position->asVector3();
        $pk->yaw = $yaw;
        $pk->pitch = $pitch;
        $pk->syncedProperties = new PropertySyncData([], []);
        /** @var TypeConverter $converter */
        $converter = TypeConverter::getInstance();
        /** @var ItemFactory $itemFactory */
        $itemFactory = ItemFactory::getInstance();
        $pk->item = ItemStackWrapper::legacy($converter->coreItemStackToNet($itemFactory->get(ItemIds::AIR)));
        $flags = (
            1 << EntityMetadataFlags::ALWAYS_SHOW_NAMETAG
            ^ 1 << EntityMetadataFlags::CAN_SHOW_NAMETAG
        );
        $pk->metadata = [
            EntityMetadataProperties::FLAGS => new LongMetadataProperty($flags),
            EntityMetadataProperties::NAMETAG => new StringMetadataProperty($this->nameTag),
            EntityMetadataProperties::LEAD_HOLDER_EID => new LongMetadataProperty(-1)
        ];
        $player->getNetworkSession()->sendDataPacket($pk);
        $this->setNameTag($player);
        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_REMOVE;
        $pk->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
        $player->getNetworkSession()->sendDataPacket($pk);
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
        $xdiff = $player->getPosition()->x - $this->position->x;
        $zdiff = $player->getPosition()->z - $this->position->z;
        $angle = atan2($zdiff, $xdiff);
        $yaw = (($angle * 180) / M_PI) - 90;
        $ydiff = $player->getPosition()->y - $this->position->y;
        $v = new Vector2($this->position->x, $this->position->z);
        $dist = $v->distance(new Vector2($player->getPosition()->x, $player->getPosition()->z));
        $angle = atan2($dist, $ydiff);
        $pitch = (($angle * 180) / M_PI) - 90;
        $pk = new MovePlayerPacket();
        $pk->actorRuntimeId = $this->entityId;
        $pk->position = $this->position->asVector3()->add(0, 1.62, 0);
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
     * @return callable
     */
    public function getCallable(): callable {
        return $this->callable;
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
    public function getPosition(): Position {
        return $this->position;
    }
}
