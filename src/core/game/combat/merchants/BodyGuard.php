<?php

namespace core\game\combat\merchants;

use core\game\combat\guards\ArtificialIntelligence;
use core\game\item\ItemManager;
use core\level\block\Ore;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use libs\utils\Utils;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Flowable;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class BodyGuard extends Human implements ArtificialIntelligence {

    const ATTACK_DISTANCE = 5;

    const FIND_DISTANCE = 15;

    const LOSE_DISTANCE = 25;

    /** @var int */
    public $attackDamage;

    /** @var float */
    public $speed;

    /** @var int */
    public $attackWait;

    /** @var int */
    public $defAttackWait = 20;

    /** @var int */
    public $regenerationWait = 0;

    /** @var int */
    public $regenerationRate;

    /** @var NexusPlayer|null */
    private $target = null;

    /** @var Position */
    private $station;

    /** @var int */
    private $jumpTicks = 5;

    /** @var int */
    private $findNewTargetTicks = 0;

    /** @var null|MerchantShop */
    private $merchantShop = null;

    /**
     * @param Location $location
     * @param Block $ore
     *
     * @return static
     */
    public static function create(Location $location, Block $ore, ?MerchantShop $shop = null): self {
        $color = ItemManager::getColorByOre($ore);
        $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR . "json" . DIRECTORY_SEPARATOR . "bodyguard.png";
        $entity = new BodyGuard($location, Utils::createSkin(Utils::getSkinDataFromPNG($path)), null);
        $entity->merchantShop = $shop;
        switch($ore->getId()) {
            case BlockLegacyIds::EMERALD_ORE:
                $health = 250;
                $damage = 8;
                $chest = VanillaItems::DIAMOND_CHESTPLATE();
                $legs = VanillaItems::DIAMOND_LEGGINGS();
                $boots = VanillaItems::DIAMOND_BOOTS();
                $sword = VanillaItems::DIAMOND_SWORD();
                break;
            case BlockLegacyIds::DIAMOND_ORE:
                $health = 225;
                $damage = 7;
                $chest = VanillaItems::IRON_CHESTPLATE();
                $legs = VanillaItems::IRON_LEGGINGS();
                $boots = VanillaItems::IRON_BOOTS();
                $sword = VanillaItems::IRON_SWORD();
                break;
            case BlockLegacyIds::GOLD_ORE:
                $health = 200;
                $damage = 6;
                $chest = VanillaItems::IRON_CHESTPLATE();
                $legs = VanillaItems::IRON_LEGGINGS();
                $boots = VanillaItems::IRON_BOOTS();
                $sword = VanillaItems::IRON_SWORD();
                break;
            case BlockLegacyIds::REDSTONE_ORE:
                $health = 175;
                $damage = 5;
                $chest = VanillaItems::GOLDEN_CHESTPLATE();
                $legs = VanillaItems::GOLDEN_LEGGINGS();
                $boots = VanillaItems::GOLDEN_BOOTS();
                $sword = VanillaItems::GOLDEN_SWORD();
                break;
            case BlockLegacyIds::LAPIS_ORE:
                $health = 150;
                $damage = 4;
                $chest = VanillaItems::GOLDEN_CHESTPLATE();
                $legs = VanillaItems::GOLDEN_LEGGINGS();
                $boots = VanillaItems::GOLDEN_BOOTS();
                $sword = VanillaItems::GOLDEN_SWORD();
                break;
            case BlockLegacyIds::IRON_ORE:
                $health = 125;
                $damage = 3;
                $chest = VanillaItems::CHAINMAIL_CHESTPLATE();
                $legs = VanillaItems::CHAINMAIL_LEGGINGS();
                $boots = VanillaItems::CHAINMAIL_BOOTS();
                $sword = VanillaItems::STONE_SWORD();
                break;
            default:
                $health = 100;
                $damage = 2;
                $chest = VanillaItems::CHAINMAIL_CHESTPLATE();
                $legs = VanillaItems::CHAINMAIL_LEGGINGS();
                $boots = VanillaItems::CHAINMAIL_BOOTS();
                $sword = VanillaItems::WOODEN_SWORD();
        }
        $entity->setMaxHealth($health);
        $entity->setHealth($health);
        $entity->setStation($location->asPosition());
        $hp = round($entity->getHealth(), 1);
        $entity->setNametag(TextFormat::BOLD . $color . "Bodyguard");
        $entity->setScoreTag(TextFormat::WHITE . $hp . TextFormat::BOLD . TextFormat::RED . " HP");
        $entity->attackDamage = $damage;
        $entity->speed = 1;
        $entity->defAttackWait = 20;
        $ench = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1);
        $chest->addEnchantment($ench);
        $entity->getArmorInventory()->setChestplate($chest);
        $legs->addEnchantment($ench);
        $entity->getArmorInventory()->setLeggings($legs);
        $boots->addEnchantment($ench);
        $entity->getArmorInventory()->setBoots($boots);
        $sword->addEnchantment($ench);
        $entity->getInventory()->setItemInHand($sword);
        return $entity;
    }

    /**
     * @param int $tickDiff
     *
     * @return bool
     */
    protected function entityBaseTick(int $tickDiff = 1): bool {
        parent::entityBaseTick($tickDiff);
        if(!$this->isAlive()) {
            if(!$this->closed) {
                $this->flagForDespawn();
            }
            return false;
        }
        if($this->station === null) {
            $this->flagForDespawn();
            return false;
        }
        $hp = round($this->getHealth(), 1);
        $this->setScoreTag(TextFormat::WHITE . $hp . TextFormat::BOLD . TextFormat::RED . " HP");
        if($this->hasTarget()) {
            return $this->attackTarget();
        }
        else {
            if($this->station !== null) {
                if(!$this->getPosition()->equals($this->station)) {
                    $this->teleport($this->station);
                    $this->updateMovement();
                }
            }
            if($this->findNewTargetTicks > 0) {
                $this->findNewTargetTicks--;
            }
            if(!$this->hasTarget() and $this->findNewTargetTicks === 0) {
                $this->findNewTarget();
            }
            return true;
        }
    }

    /**
     * @return bool
     */
    public function attackTarget(): bool {
        $target = $this->getTarget();
        if($target == null or $this->station->distance($this->getPosition()) >= self::LOSE_DISTANCE) {
            $this->target = null;
            return true;
        }
        if(!$target->isAlive()) {
            $this->target = null;
        }
        if($this->jumpTicks > 0) {
            $this->jumpTicks--;
        }
        if(!$this->isOnGround()) {
            if($this->motion->y > -$this->gravity * 4) {
                $this->motion->y = -$this->gravity * 4;
            }
            else {
                $this->motion->y += $this->isUnderwater() ? $this->gravity : -$this->gravity;
            }
        }
        else {
            $this->motion->y -= $this->gravity;
        }
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if($this->shouldJump()) {
            $this->jump();
        }
        $x = $target->getPosition()->x - $this->getPosition()->x;
        $y = $target->getPosition()->y - $this->getPosition()->y;
        $z = $target->getPosition()->z - $this->getPosition()->z;
        if($x * $x + $z * $z < 1.2) {
            $this->motion->x = 0;
            $this->motion->z = 0;
        }
        else {
            $this->motion->x = $this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->z = $this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
        }
        $this->location->yaw = rad2deg(atan2(-$x, $z));
        $this->location->pitch = rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if($this->shouldJump()) {
            $this->jump();
        }
        if($this->getPosition()->distance($target->getPosition()) <= self::ATTACK_DISTANCE and $this->attackWait <= 0 and (!$target->isClosed())) {
            $damage = $this->attackDamage;
            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_CUSTOM, $damage, []);
            $ev->call();
            if(!$ev->isCancelled()) {
                $target->attack($ev);
            }
            if($target->isTagged()) {
                $target->combatTag();
            }
            else {
                $target->combatTag();
                $target->sendMessage(Translation::getMessage("combatTag"));
            }
            if($target->isLoaded()) {
                if($target->getCESession()->getFrenzyHits() > 0) {
                    $target->getCESession()->resetFrenzyHits();
                }
            }
            $this->broadcastAnimation(new ArmSwingAnimation($this));
            $this->attackWait = $this->defAttackWait;
        }
        $this->updateMovement();
        $this->attackWait--;
        return $this->isAlive();
    }

    /**
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source): void {
        if($source instanceof EntityDamageByEntityEvent) {
            $killer = $source->getDamager();
            if($killer instanceof NexusPlayer) {
                if($killer->isFlying() or $killer->getAllowFlight() == true) {
                    $killer->setFlying(false);
                    $killer->setAllowFlight(false);
                }
                if($this->target === null or $this->target->getName() != $killer->getName()) {
                    $this->target = $killer;
                }
                if($this->merchantShop !== null) {
                    $merchant = $this->merchantShop->getTempMerchant();
                    if($merchant !== null && !$merchant->isClosed()) {
                        $this->merchantShop->getTempMerchant()->resetActivity();
                    }
                }
            }
            $source->setKnockBack(0.0);
        } elseif($source->getCause() === EntityDamageEvent::CAUSE_FALL) {
            $source->cancel();
        }
        parent::attack($source);
    }

    /**
     * @return array
     */
    public function getDrops(): array {
        return [];
    }

    /**
     * @return bool
     */
    public function hasTarget(): bool {
        $target = $this->getTarget();
        if($target == null || !$target->isOnline()) {
            return false;
        }
        return true;
    }

    /**
     * @param Player|null $target
     */
    public function setTarget(?Player $target): void {
        $this->target = $target;
    }

    /**
     * @return NexusPlayer|null
     */
    public function getTarget(): ?NexusPlayer {
        return $this->target;
    }

    /**
     * @return float
     */
    public function getSpeed(): float {
        return ($this->isUnderwater() ? $this->speed / 2 : $this->speed);
    }

    /**
     * @return int
     */
    public function getBaseAttackDamage(): int {
        return $this->attackDamage;
    }

    /**
     * @return Position
     */
    public function getStation(): Position {
        return $this->station;
    }

    /**
     * @param Position $station
     */
    public function setStation(Position $station): void {
        $this->station = $station;
    }

    public function findNewTarget() {
        $distance = self::FIND_DISTANCE;
        $target = null;
        foreach($this->getWorld()->getPlayers() as $player) {
            if($player instanceof self) {
                continue;
            }
            if($player instanceof NexusPlayer and $player->getPosition()->distance($this->getPosition()) <= $distance and (!$player->isCreative())) {
                $distance = $player->getPosition()->distance($this->getPosition());
                $target = $player;
            }
        }
        $this->findNewTargetTicks = 60;
        $this->target = ($target != null ? $target : null);
    }

    /**
     * @param int $y
     *
     * @return Block
     */
    public function getFrontBlock($y = 0): Block {
        $dv = $this->getDirectionVector();
        $pos = $this->getPosition()->add($dv->x * $this->getScale(), $y + 1, $dv->z * $this->getScale())->round();
        return $this->getWorld()->getBlock($pos);
    }

    /**
     * @return bool
     */
    public function shouldJump(): bool {
        if($this->jumpTicks > 0) {
            return false;
        }
        return $this->isCollidedHorizontally or
            ($this->getFrontBlock()->getId() != 0 or $this->getFrontBlock(-1) instanceof Stair) or
            ($this->getWorld()->getBlock($this->getPosition()->add(0, -0, 5)) instanceof Slab and
                (!$this->getFrontBlock(-0.5) instanceof Slab and $this->getFrontBlock(-0.5)->getId() != 0)) and
            $this->getFrontBlock(1)->getId() == 0 and
            $this->getFrontBlock(2)->getId() == 0 and
            !$this->getFrontBlock() instanceof Flowable and
            $this->jumpTicks == 0;
    }

    /**
     * @return int
     */
    public function getJumpMultiplier(): int {
        if($this->getFrontBlock() instanceof Slab or $this->getFrontBlock() instanceof Stair or
            $this->getWorld()->getBlock($this->getPosition()->subtract(0, 0.5, 0)->round()) instanceof Slab and
            $this->getFrontBlock()->getId() != 0) {
            $fb = $this->getFrontBlock();
            if($fb instanceof Slab and $fb->getMeta() & 0x08 > 0) {
                return 8;
            }
            if($fb instanceof Stair and $fb->getMeta() & 0x04 > 0) {
                return 8;
            }
            return 4;
        }
        return 16;
    }

    public function jump(): void {
        $this->motion->y = $this->gravity * $this->getJumpMultiplier();
        $this->move($this->motion->x * 1.25, $this->motion->y, $this->motion->z * 1.25);
        $this->jumpTicks = 5;
    }
}