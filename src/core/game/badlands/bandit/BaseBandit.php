<?php

namespace core\game\badlands\bandit;

use core\game\item\types\vanilla\Armor;
use core\game\zone\BadlandsRegion;
use core\Nexus;
use libs\utils\Utils;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Flowable;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

abstract class BaseBandit extends Human {

    const DEFAULT_DAMAGE = 0.75;

    public null|BadlandsRegion $region = null;
    public float $attackDamage = 0.75;
    public float $speed = 2;
    public float $range = 20;
    public string $networkId;
    public int $attackRate = 15;
    public int $attackDelay = 0;
    public int $knockbackTicks = 0;
    public int $attackRange = 3;
    public Item $heldItem;
    public int $tillDespawn = 12000;
    public float $modifier = 0.9;

    public int $jumpTicks = 20;

    public function __construct(Location $location, CompoundTag $nbt, ?BadlandsRegion $region = null)
    {
        $this->region = $region;
        if($this->region === null) {
            $this->region = Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlands()[0];
        }
        $this->heldItem = match ($this->region->getTier()) {
            Armor::TIER_CHAIN => VanillaItems::STONE_SWORD(),
            Armor::TIER_GOLD => VanillaItems::GOLDEN_SWORD(),
            Armor::TIER_IRON => VanillaItems::IRON_SWORD(),
            default => VanillaItems::DIAMOND_SWORD(),
        };
        $skin = Utils::createSkin(Utils::getSkinDataFromPNG(Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR . "bandit.png"));
        parent::__construct($location, $skin, $nbt);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->setMaxHealth(250);
        $this->setHealth(250);
        $this->initHumanData($nbt);
        switch ($this->region->getTier()){
            case Armor::TIER_CHAIN:
                $this->getArmorInventory()->setHelmet(VanillaItems::CHAINMAIL_HELMET());
                $this->getArmorInventory()->setChestplate(VanillaItems::CHAINMAIL_CHESTPLATE());
                $this->getArmorInventory()->setLeggings(VanillaItems::CHAINMAIL_LEGGINGS());
                $this->getArmorInventory()->setBoots(VanillaItems::CHAINMAIL_BOOTS());
                break;
            case Armor::TIER_GOLD:
                $this->getArmorInventory()->setHelmet(VanillaItems::GOLDEN_HELMET());
                $this->getArmorInventory()->setChestplate(VanillaItems::GOLDEN_CHESTPLATE());
                $this->getArmorInventory()->setLeggings(VanillaItems::GOLDEN_LEGGINGS());
                $this->getArmorInventory()->setBoots(VanillaItems::GOLDEN_BOOTS());
                break;
            case Armor::TIER_IRON:
                $this->getArmorInventory()->setHelmet(VanillaItems::IRON_HELMET());
                $this->getArmorInventory()->setChestplate(VanillaItems::IRON_CHESTPLATE());
                $this->getArmorInventory()->setLeggings(VanillaItems::IRON_LEGGINGS());
                $this->getArmorInventory()->setBoots(VanillaItems::IRON_BOOTS());
                break;
            case Armor::TIER_DIAMOND:
                $this->getArmorInventory()->setHelmet(VanillaItems::DIAMOND_HELMET());
                $this->getArmorInventory()->setChestplate(VanillaItems::DIAMOND_CHESTPLATE());
                $this->getArmorInventory()->setLeggings(VanillaItems::DIAMOND_LEGGINGS());
                $this->getArmorInventory()->setBoots(VanillaItems::DIAMOND_BOOTS());
                break;
            default:
                $this->getArmorInventory()->setHelmet(VanillaItems::LEATHER_CAP());
                $this->getArmorInventory()->setChestplate(VanillaItems::LEATHER_TUNIC());
                $this->getArmorInventory()->setLeggings(VanillaItems::LEATHER_PANTS());
                $this->getArmorInventory()->setBoots(VanillaItems::LEATHER_BOOTS());
                break;
        }
        $this->setNameTagAlwaysVisible();
    }

    public function getNametag(): string
    {
        if(isset($this->region)) {
            $color = match ($this->region->getTier()) {
                Armor::TIER_CHAIN => TextFormat::GRAY,
                Armor::TIER_GOLD => TextFormat::GOLD,
                Armor::TIER_IRON => TextFormat::WHITE,
                Armor::TIER_DIAMOND => TextFormat::AQUA
            };
        } else {
            $color = TextFormat::GRAY;
        }
        return $color . TextFormat::BOLD . $this->getName();
    }

    public function getHP() : string
    {
        return TextFormat::WHITE . $this->getHealth() . TextFormat::BOLD . TextFormat::RED . " HP";
    }

    public function onUpdate(int $currentTick): bool
    {
        $this->tillDespawn--;

        if($this->tillDespawn <= 0) {
            $this->flagForDespawn();
            return false;
        }
        if($this->region !== null && $this->attackDamage === self::DEFAULT_DAMAGE) {
            $this->attackDamage = match ($this->region->getTier()) {
                Armor::TIER_CHAIN => mt_rand(1, 2) * 0.5,
                Armor::TIER_GOLD => mt_rand(1, 2),
                Armor::TIER_IRON => mt_rand(2, 3),
                default => mt_rand(3, 5),
            };
        }

        if(!$this->isAlive()){
            if(!$this->closed) $this->flagForDespawn();
            return false;
        }

        $this->setNametag($this->getNametag() . "\n" . $this->getHP());

        if($this->knockbackTicks > 0) $this->knockbackTicks--;

        if ($this->jumpTicks > 0) $this->jumpTicks--;

        if(!$this->isAlive()) return false;

        $player = $this->getTargetEntity();

        if(!$player instanceof Player) {
            foreach($this->getViewers() as $viewer) {
                if(!$viewer->isSpectator() && $viewer->isAlive() && $viewer->location->distanceSquared($this->location) < 15) {
                    $player = $viewer;
                }
            }
            $this->setTargetEntity($player);
        }

        if($player instanceof Living && $player->getWorld() === $this->getWorld() && $player->isAlive() && !$player->isClosed()) {
            if(!$this->isOnGround()) {
                if ($this->motion->y > -$this->gravity * 4) {
                    $this->motion->y = -$this->gravity * 4;
                } else {
                    $this->motion->y -= $this->gravity;
                }
            }

            if($this->knockbackTicks <= 0) {
                $x = $player->location->x - $this->location->x;
                $y = $player->location->y - $this->location->y;
                $z = $player->location->z - $this->location->z;
                if ($x ** 2 + $z ** 2 < 0.7) {
                    $this->motion->x = 0;
                    $this->motion->z = 0;
                } else {
                    $diff = abs($x) + abs($z);
                    $this->motion->x = $this->speed * 0.15 * ($x / $diff);
                    if (!$this->gravityEnabled) {
                        $this->motion->y = $this->speed * 0.15 * ($y / $diff);
                    }
                    $this->motion->z = $this->speed * 0.15 * ($z / $diff);
                }
                $this->location->yaw = rad2deg(atan2(-$x, $z));
                $this->location->pitch = rad2deg(atan(-$y));
                $this->move($this->motion->x, $this->motion->y, $this->motion->z);
                if ($this->shouldJump()) $this->jump();
                if ($this->isCollidedHorizontally) {
                    $this->motion->y = ($this->gravityEnabled ? 0 : mt_rand(0, 1)) === 0 ? $this->jumpVelocity : -$this->jumpVelocity;
                }

                if($this->attackDelay > $this->attackRate) {
                    if($player->location->distance($this->location) < $this->attackRange) {
                        $this->attackDelay = 0;
                        $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->attackDamage);
                        $player->attack($ev);
                        $this->broadcastAnimation(new ArmSwingAnimation($this));
                    }
                }
                $this->attackDelay++;

            } else {
                $this->move($this->motion->x, $this->motion->y, $this->motion->z);
                if ($this->shouldJump()) $this->jump();
            }
            $this->updateMovement();
        }
        parent::onUpdate($currentTick);
        return !$this->isClosed();
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->setBaseDamage($source->getBaseDamage() * $this->modifier);


        parent::attack($source);

        if (!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent) {
            $dmg = $source->getDamager();
            if ($dmg instanceof Player) {
                $this->setTargetEntity($dmg);
                $this->knockbackTicks = 5;
                $this->tillDespawn = 12000;
            }
        }
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $networkSession = $player->getNetworkSession();
        if(!($this instanceof Player)){
            $networkSession->sendDataPacket(PlayerListPacket::add([PlayerListEntry::createAdditionEntry($this->uuid, $this->id, $this->getName(), SkinAdapterSingleton::get()->toSkinData($this->skin))]));
        }

        $networkSession->sendDataPacket(AddPlayerPacket::create(
            $this->getUniqueId(),
            $this->getName(),
            $this->getId(),
            "",
            $this->location->asVector3(),
            $this->getMotion(),
            $this->location->pitch,
            $this->location->yaw,
            $this->location->yaw, //TODO: head yaw
            ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($this->getInventory()->getItemInHand())),
            GameMode::SURVIVAL,
            $this->getAllNetworkData(),
            new PropertySyncData([], []),
            UpdateAbilitiesPacket::create(new AbilitiesData(CommandPermissions::NORMAL, PlayerPermissions::VISITOR, $this->getId() /* TODO: this should be unique ID */, [
                new AbilitiesLayer(
                    AbilitiesLayer::LAYER_BASE,
                    array_fill(0, AbilitiesLayer::NUMBER_OF_ABILITIES, false),
                    0.0,
                    0.0
                )
            ])),
            [], //TODO: entity links
            "", //device ID (we intentionally don't send this - secvuln)
            DeviceOS::UNKNOWN //we intentionally don't send this (secvuln)
        ));
        if(!($this instanceof Player)){
            $networkSession->sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($this->uuid)]));
        }
        return;
        $player->getNetworkSession()->getEntityEventBroadcaster()->onMobArmorChange([$player->getNetworkSession()], $this);
        $player->getNetworkSession()->sendDataPacket(MobEquipmentPacket::create($this->getId(), ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($this->heldItem)), 0, 0, ContainerIds::OFFHAND));
    }

    public function shouldJump(): bool
    {
        if ($this->jumpTicks > 0) return false;
        return $this->isCollidedHorizontally ||
            ($this->getFrontBlock()->getId() != 0 || $this->getFrontBlock(-1) instanceof Stair) ||
            ($this->getWorld()->getBlock($this->getLocation()->asVector3()->add(0, -0, 5)) instanceof Slab &&
                (!$this->getFrontBlock(-0.5) instanceof Slab && $this->getFrontBlock(-0.5)->getId() != 0)) &&
            $this->getFrontBlock(1) instanceof Air &&
            $this->getFrontBlock(2) instanceof Air &&
            !$this->getFrontBlock() instanceof Flowable &&
            $this->jumpTicks == 0;
    }

    public function getFrontBlock($y = 0): Block
    {
        $dv = $this->getDirectionVector();
        $pos = $this->location->asVector3()->add($dv->x * $this->getScale(), $y + 1, $dv->z * $this->getScale())->round();
        return $this->getWorld()->getBlock($pos);
    }

    public function jump(): void
    {
        $this->motion->y = $this->gravity * 12;
        $this->move($this->motion->x * 1.25, $this->motion->y, $this->motion->z * 1.25);
        $this->jumpTicks = 5;
    }
}