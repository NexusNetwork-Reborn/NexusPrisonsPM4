<?php

namespace core\game\combat\merchants;

use core\game\combat\guards\ArtificialIntelligence;
use core\game\combat\merchants\event\KillMerchantEvent;
use core\game\item\ItemManager;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\custom\MysteryTrinketBox;
use core\game\item\types\custom\OreGenBooster;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\Token;
use core\game\item\types\custom\XPBooster;
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
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class OreMerchant extends Human implements ArtificialIntelligence {

    const ATTACK_DISTANCE = 5;

    const FIND_DISTANCE = 10;

    const LOSE_DISTANCE = 25;

    /** @var int */
    public $attackDamage;

    /** @var float */
    public $speed;

    /** @var NexusPlayer|null */
    private $target = null;

    /** @var Position */
    private $station;

    /** @var bool */
    private $heroic = false;

    /** @var int */
    private $jumpTicks = 5;

    /** @var int */
    private $sneakTicks = 2;

    /** @var BodyGuard */
    private $guards = [];

    /** @var Block */
    private $ore;

    /** @var int */
    private $shopId;

    /** @var int */
    private $lastDeath = 0;

    /** @var int */
    private $activityTicks = 0;

    /**
     * @param Location $location
     * @param Block $ore
     * @param int $id
     * @param array $guards
     *
     * @return static
     */
    public static function create(Location $location, Block $ore, int $id, array $guards): self {
        $color = ItemManager::getColorByOre($ore);
        $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR . "json" . DIRECTORY_SEPARATOR . "merchant.png";
        $entity = new OreMerchant($location, Utils::createSkin(Utils::getSkinDataFromPNG($path)), null);
        switch($ore->getId()) {
            case BlockLegacyIds::EMERALD_ORE:
                $health = 750;
                $chest = VanillaItems::DIAMOND_CHESTPLATE();
                $legs = VanillaItems::DIAMOND_LEGGINGS();
                $boots = VanillaItems::DIAMOND_BOOTS();
                break;
            case BlockLegacyIds::DIAMOND_ORE:
                $health = 675;
                $chest = VanillaItems::IRON_CHESTPLATE();
                $legs = VanillaItems::IRON_LEGGINGS();
                $boots = VanillaItems::IRON_BOOTS();
                break;
            case BlockLegacyIds::GOLD_ORE:
                $health = 625;
                $chest = VanillaItems::IRON_CHESTPLATE();
                $legs = VanillaItems::IRON_LEGGINGS();
                $boots = VanillaItems::IRON_BOOTS();
                break;
            case BlockLegacyIds::REDSTONE_ORE:
                $health = 575;
                $chest = VanillaItems::GOLDEN_CHESTPLATE();
                $legs = VanillaItems::GOLDEN_LEGGINGS();
                $boots = VanillaItems::GOLDEN_BOOTS();
                break;
            case BlockLegacyIds::LAPIS_ORE:
                $health = 550;
                $chest = VanillaItems::GOLDEN_CHESTPLATE();
                $legs = VanillaItems::GOLDEN_LEGGINGS();
                $boots = VanillaItems::GOLDEN_BOOTS();
                break;
            case BlockLegacyIds::IRON_ORE:
                $health = 525;
                $chest = VanillaItems::CHAINMAIL_CHESTPLATE();
                $legs = VanillaItems::CHAINMAIL_LEGGINGS();
                $boots = VanillaItems::CHAINMAIL_BOOTS();
                break;
            default:
                $health = 500;
                $chest = VanillaItems::CHAINMAIL_CHESTPLATE();
                $legs = VanillaItems::CHAINMAIL_LEGGINGS();
                $boots = VanillaItems::CHAINMAIL_BOOTS();
        }
        $entity->setMaxHealth($health);
        $entity->setHealth($health);
        $entity->setStation($location);
        $hp = round($entity->getHealth(), 1);
        $entity->setNametag(TextFormat::BOLD . $color . "Merchant");
        $entity->setScoreTag(TextFormat::WHITE . $hp . TextFormat::BOLD . TextFormat::RED . " HP");
        $entity->attackDamage = 8;
        $entity->speed = 1;
        $entity->defAttackWait = 20;
        $ench = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1);
        $chest->addEnchantment($ench);
        $entity->getArmorInventory()->setChestplate($chest);
        $legs->addEnchantment($ench);
        $entity->getArmorInventory()->setLeggings($legs);
        $boots->addEnchantment($ench);
        $entity->getArmorInventory()->setBoots($boots);
        $entity->setGuards($guards);
        $entity->setOre($ore);
        $entity->setShopId($id);
        return $entity;
    }

    /**
     * @param int $tickDiff
     *
     * @return bool
     */
    protected function entityBaseTick(int $tickDiff = 1): bool {
        parent::entityBaseTick($tickDiff);
        if($this->ore === null) {
            $this->flagForDespawn();
            return false;
        }
        if(!$this->isAlive()) {
            if(!$this->closed) {
                $this->flagForDespawn();
            }
            return false;
        }
        if(!empty($this->guards)) {
            /** @var BodyGuard $guard */
            foreach($this->guards as $index => $guard) {
                if((!$guard->isAlive()) or $guard->isClosed()) {
                    unset($this->guards[$index]);
                }
            }
        }
//        $bb = $this->getBoundingBox()->expandedCopy(24, 24, 24);
//        foreach($this->getWorld()->getNearbyEntities($bb, $this) as $player) {
//            if($player instanceof NexusPlayer && !isset($this->spawned[$player->getUniqueId()->toString()])) {
//                $this->spawnTo($player);
//            }
//        }
        if(--$this->sneakTicks <= 0) {
            $this->location->pitch = 0;
            $this->location->yaw = ($this->location->yaw + 10) > 360 ? 0 : ($this->location->yaw + 10);
            $this->setSneaking(!$this->isSneaking());
            $this->sendData($this->getViewers());
            $this->updateMovement();
            $this->sneakTicks = 2;
            $hp = round($this->getHealth(), 1);
            $this->setScoreTag(TextFormat::WHITE . $hp . TextFormat::BOLD . TextFormat::RED . " HP");
        }
        if($this->activityTicks > 0) {
            $this->activityTicks--;
        }
        return true;
    }

    /**
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source): void {
        if($source instanceof EntityDamageByEntityEvent) {
            $killer = $source->getDamager();
            if($killer instanceof NexusPlayer) {
                $this->activityTicks = 600;
                if(!empty($this->guards)) {
                    $source->cancel();
                    $killer->playErrorSound();
                    $killer->sendMessage(TextFormat::DARK_GRAY . "[" . $this->getNameTag() . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::RED . "Haha! With my guards, I'm invincible!");
                    return;
                }
                if($killer->isFlying() or $killer->getAllowFlight() == true) {
                    $killer->setFlying(false);
                    $killer->setAllowFlight(false);
                }
                if($this->target === null or $this->target->getName() != $killer->getName()) {
                    $this->target = $killer;
                }
            }
            $source->setKnockBack(0.0);
        }
        parent::attack($source);
    }

    /**
     * @return bool
     */
    public function isActive() : bool {
        return $this->activityTicks > 0;
    }

    /**
     * @return void
     */
    public function resetActivity() : void {
        $this->activityTicks = 600;
    }

//    /** @var NexusPlayer[] */
//    private $spawned = [];
//
//    public function spawnTo(Player $player): void
//    {
//        $this->spawned[$player->getUniqueId()->toString()] = $player;
//        parent::spawnTo($player);
//    }
//
//    public function despawnFrom(Player $player, bool $send = true): void
//    {
//        unset($this->spawned[$player->getUniqueId()->toString()]);
//        parent::despawnFrom($player, $send); // TODO: Change the autogenerated stub
//    }

    /**
     * @param int $shopId
     */
    public function setShopId(int $shopId): void {
        $this->shopId = $shopId;
    }

    protected function onDeath(): void {
        if(time() - $this->lastDeath <= 3) {
            return;
        }
        $this->lastDeath = time();
        $this->startDeathAnimation();
        $cause = $this->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            if($killer instanceof NexusPlayer) {
                if($this->shopId !== null) {
                    Nexus::getInstance()->getGameManager()->getCombatManager()->resetMerchantShop($this->shopId, true);
                }
                $color = ItemManager::getColorByOre($this->ore);
                $x = $killer->getPosition()->getFloorX();
                $y = $killer->getPosition()->getFloorY();
                $z = $killer->getPosition()->getFloorZ();
                Server::getInstance()->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . "(!)" . TextFormat::RESET . TextFormat::GOLD . " A " . $color . $this->ore->getName() . " Merchant" . TextFormat::GOLD . " has been slained by " . TextFormat::WHITE . $killer->getName() . TextFormat::GOLD . " at " . TextFormat::RESET . TextFormat::WHITE . "$x" . TextFormat::GRAY . "x, " . TextFormat::WHITE . "$y" . TextFormat::GRAY . "y, " . TextFormat::WHITE . "$z" . TextFormat::GRAY . "z");
                switch($this->ore->getId()) {
                    case BlockLegacyIds::EMERALD_ORE:
                        $multiplier = 20;
                        break;
                    case BlockLegacyIds::DIAMOND_ORE:
                        $multiplier = 16;
                        break;
                    case BlockLegacyIds::GOLD_ORE:
                        $multiplier = 12;
                        break;
                    case BlockLegacyIds::REDSTONE_ORE:
                        $multiplier = 8;
                        break;
                    case BlockLegacyIds::LAPIS_ORE:
                        $multiplier = 4;
                        break;
                    case BlockLegacyIds::IRON_ORE:
                        $multiplier = 2;
                        break;
                    default:
                        $multiplier = 1;
                }
                $ev = new KillMerchantEvent($killer, $this->ore->getId());
                $ev->call();
                $energyMin = 125000 * $multiplier;
                $energyMax = 250000 * $multiplier;
                $killer->addItem((new Energy(mt_rand($energyMin, $energyMax)))->toItem(), true);
                $moneyMin = 100000 * $multiplier;
                $moneyMax = 250000 * $multiplier;
                $killer->addItem((new MoneyNote(mt_rand($moneyMin, $moneyMax)))->toItem(), true);
                $tokenMin = (int)ceil(1 * ($multiplier / 2));
                $tokenMax = (int)ceil(3 * ($multiplier / 2));
                $killer->addItem((new Token())->toItem()->setCount(mt_rand($tokenMin, $tokenMax)), true);
                if(mt_rand(1, 2) === 1) {
                    switch(mt_rand(1, 3)) {
                        case 1:
                            $item = (new XPBooster(1 + (mt_rand(0, 25) * 0.1), mt_rand(10, 60)))->toItem()->setCount(1);
                            break;
                        case 2:
                            $item = (new OreGenBooster(mt_rand(15, 200)))->toItem()->setCount(1);
                            break;
                        default:
                            $item = (new EnergyBooster(1 + (mt_rand(0, 25) * 0.1), mt_rand(10, 60)))->toItem()->setCount(1);
                            break;
                    }
                    $killer->addItem($item, true);
                }
                if(mt_rand(1, 3) === 1) {
                    $killer->addItem((new Satchel($this->ore->asItem()))->toItem(), true);
                }
                if(mt_rand(1, 5) === mt_rand(1, 5)) {
                    $killer->addItem((new MysteryTrinketBox())->toItem(), true);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getDrops(): array {
        return [];
    }

    /**
     * @param BodyGuard $guards
     */
    public function setGuards(array|BodyGuard $guards): void {
        $this->guards = $guards;
        if(count($guards) >= 4) {
            $this->heroic = true;
        }
    }

    /**
     * @return bool
     */
    public function hasTarget(): bool {
        $target = $this->getTarget();
        if($target == null) {
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

    /**
     * @param Block $ore
     */
    public function setOre(Block $ore): void {
        $this->ore = $ore;
    }
}