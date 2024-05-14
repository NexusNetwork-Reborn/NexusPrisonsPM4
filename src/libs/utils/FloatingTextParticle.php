<?php

declare(strict_types = 1);

namespace libs\utils;

use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use Ramsey\Uuid\Uuid;

class FloatingTextParticle extends \pocketmine\world\particle\FloatingTextParticle {

    /** @var string */
    private $identifier;

    /** @var string */
    private $message;

    /** @var World */
    private $level;

    /** @var Position */
    private $position;

    /** @var null|Skin */
    private $skin;

    /**
     * FloatingTextParticle constructor.
     *
     * @param Position $pos
     * @param string $identifier
     * @param string $message
     */
    public function __construct(Position $pos, string $identifier, string $message, ?Skin $skin = null) {
        parent::__construct("", "");
        $this->level = $pos->getWorld();
        $this->position = $pos;
        $this->identifier = $identifier;
        $this->message = $message;
        $this->skin = $skin;
        $this->update();
    }

    /**
     * @return string
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * @return World
     */
    public function getLevel(): World {
        return $this->level;
    }

    /**
     * @param null|string $message
     */
    public function update(?string $message = null): void {
        $this->message = $message ?? $this->message;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string {
        return $this->identifier;
    }

    public function sendChangesToAll(): void {
        foreach(Server::getInstance()->getOnlinePlayers() as $player) {
            $this->sendChangesTo($player);
        }
    }

    /**
     * @param Position $position
     */
    public function move(Position $position) {
        $this->position = $position;
    }

    /**
     * @param Player $player
     */
    public function sendChangesTo(Player $player): void {
        $this->setTitle($this->message);
        $level = $player->getWorld();
        if($level === null) {
            return;
        }
        if($this->level->getFolderName() !== $level->getFolderName()) {
            return;
        }
        $this->level->addParticle($this->position, $this, [$player]);
    }

    /**
     * @param Player $player
     */
    public function spawn(Player $player): void {
        $this->setInvisible(false);
        $level = $player->getWorld();
        if($level === null) {
            return;
        }
        $this->level->addParticle($this->position, $this, [$player]);
    }

    /**
     * @param Player $player
     */
    public function despawn(Player $player): void {
        $this->setInvisible(true);
        $level = $player->getWorld();
        if($level === null) {
            return;
        }
        $this->level->addParticle($this->position, $this, [$player]);
    }

    public function encode(Vector3 $pos): array
    {
        $p = [];

        if($this->entityId === null){
            $this->entityId = Entity::nextRuntimeId();
        }

        if(!$this->invisible){
            $uuid = Uuid::uuid4();
            $name = $this->title . ($this->text !== "" ? "\n" . $this->text : "");

            if($this->skin === null) {
                $p[] = PlayerListPacket::add([PlayerListEntry::createAdditionEntry($uuid, $this->entityId, $name, SkinAdapterSingleton::get()->toSkinData(new Skin("Standard_Custom", str_repeat("\x00", 8192))))]);
            } else {
                $p[] = PlayerListPacket::add([PlayerListEntry::createAdditionEntry($uuid, $this->entityId, $name, SkinAdapterSingleton::get()->toSkinData($this->skin))]);
            }

            $actorFlags = (
                1 << EntityMetadataFlags::IMMOBILE
            );
            $actorMetadata = [
                EntityMetadataProperties::FLAGS => new LongMetadataProperty($actorFlags),
                EntityMetadataProperties::SCALE => new FloatMetadataProperty(0.01) //zero causes problems on debug builds
            ];
            $p[] = AddPlayerPacket::create(
                $uuid,
                $name,
                $this->entityId, //TODO: actor unique ID
                "",
                $pos, //TODO: check offset
                null,
                0,
                0,
                0,
                ItemStackWrapper::legacy(ItemStack::null()),
                GameMode::SURVIVAL,
                $actorMetadata,
                new PropertySyncData([], []),
                UpdateAbilitiesPacket::create(new AbilitiesData(0, 0, $this->entityId, [])),
                [],
                "",
                DeviceOS::UNKNOWN
            );

            if($this->skin !== null) {
                $pk = new PlayerSkinPacket();
                $pk->uuid = $uuid;
                $pk->skin = SkinAdapterSingleton::get()->toSkinData($this->skin);
                $p[] = $pk;
            }

            $p[] = PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($uuid)]);
        } else {
            $actorFlags = (
                1 << EntityMetadataFlags::INVISIBLE
            );
            $p[] = SetActorDataPacket::create(
                $this->entityId,
                [EntityMetadataProperties::FLAGS => new LongMetadataProperty($actorFlags)],
                new PropertySyncData([], []),
                0
            );
            $p[] = RemoveActorPacket::create($this->entityId);
        }

        return $p;
    }
}
