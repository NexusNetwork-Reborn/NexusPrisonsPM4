<?php

declare(strict_types=1);

namespace core\game\combat;

use core\game\boss\entity\HomingSkull;
use core\game\boss\task\BossSummonTask;
use core\game\combat\guards\Guard;
use core\game\combat\guards\GuardListener;
use core\game\combat\guards\types\Enforcer;
use core\game\combat\guards\types\Safeguard;
use core\game\combat\guards\types\Warden;
use core\game\combat\koth\KOTHArena;
use core\game\combat\koth\KOTHSchedulingTask;
use core\game\combat\koth\task\KOTHHeartbeatTask;
use core\game\combat\merchants\BodyGuard;
use core\game\combat\merchants\MerchantShop;
use core\game\combat\merchants\OreMerchant;
use core\game\combat\merchants\task\SpawnOreMerchantTask;
use core\game\wormhole\entity\ExecutiveEnderman;
use core\level\LevelManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use customiesdevs\customies\entity\CustomiesEntityFactory;
use core\game\boss\entity\Hades;
use core\game\boss\entity\HadesMinion;
use libs\utils\Utils;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use pocketmine\world\World;

class CombatManager
{

    /** @var Nexus */
    private $core;

    /** @var CombatListener */
    private $listener;

    /** @var null|KOTHArena */
    private $kothGame = null;

    /** @var KOTHArena */
    private $kothArena;

    /** @var Guard[] */
    private $guards = [];

    /** @var MerchantShop[] */
    private $merchantShops = [];

    /** @var int[] */
    private $usedShops = [];

    /**
     * CombatManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core)
    {
        $this->core = $core;
        $this->listener = new CombatListener($core);
        $core->getServer()->getPluginManager()->registerEvents($this->listener, $core);
        $core->getServer()->getPluginManager()->registerEvents(new GuardListener($core), $core);
        $this->core->getScheduler()->scheduleRepeatingTask(new KOTHHeartbeatTask($this), 20); // TODO: KOTH Heartbeat
        $this->core->getScheduler()->scheduleDelayedRepeatingTask(new KOTHSchedulingTask($core), 0, 20 * 60);
        $this->core->getScheduler()->scheduleDelayedRepeatingTask(new SpawnOreMerchantTask($this), 1200, 20);
        $this->core->getScheduler()->scheduleDelayedTask(new BossSummonTask(0), 300 * 20);
        $this->init();
    }

    /**
     * @param NexusPlayer $player
     * @param int $maxTax
     *
     * @return int
     */
    public static function calculateGuardTax(NexusPlayer $player): int
    {
        $bb = $player->getBoundingBox()->expandedCopy(12, 12, 12);
        $level = $player->getWorld();
        $tax = 0;
        if ($level !== null) {
            $guards = Nexus::getInstance()->getGameManager()->getCombatManager()->getNearbyGuards($bb, $level);
            foreach ($guards as $guard) {
                $d = abs($guard->getStation()->distance($player->getPosition())) + 0.1;
                $tax += 24 / $d;
            }
        }
        return min((int)floor($tax), 10);
    }

    /**
     * @param AxisAlignedBB $bb
     * @param World $world
     *
     * @return Guard[]
     */
    public function getNearbyGuards(AxisAlignedBB $bb, World $world): array
    {
        $nearby = [];
        foreach ($this->guards as $guard) {
            if ($guard->getBoundingBox()->intersectsWith($bb) and $guard->getStation()->getWorld()->getFolderName() === $world->getFolderName()) {
                $nearby[] = $guard;
            }
        }
        return $nearby;
    }

    public function init(): void
    {
        EntityFactory::getInstance()->register(Enforcer::class, function (World $world, CompoundTag $nbt): Enforcer {
            return new Enforcer(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Enforcer"]);
        EntityFactory::getInstance()->register(Safeguard::class, function (World $world, CompoundTag $nbt): Safeguard {
            return new Safeguard(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Safeguard"]);
        EntityFactory::getInstance()->register(Warden::class, function (World $world, CompoundTag $nbt): Warden {
            return new Warden(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Warden"]);
        EntityFactory::getInstance()->register(BodyGuard::class, function (World $world, CompoundTag $nbt): BodyGuard {
            $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR . "json" . DIRECTORY_SEPARATOR . "bodyguard.png";
            return new BodyGuard(EntityDataHelper::parseLocation($nbt, $world), Utils::createSkin(Utils::getSkinDataFromPNG($path)), $nbt);
        }, ["BodyGuard"]);
        EntityFactory::getInstance()->register(OreMerchant::class, function (World $world, CompoundTag $nbt): OreMerchant {
            $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR . "json" . DIRECTORY_SEPARATOR . "merchant.png";
            return new OreMerchant(EntityDataHelper::parseLocation($nbt, $world), Utils::createSkin(Utils::getSkinDataFromPNG($path)), $nbt);
        }, ["OreMerchant"]);
        EntityFactory::getInstance()->register(HadesMinion::class, function (World $world, CompoundTag $nbt): HadesMinion {
            return new HadesMinion(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["HadesMinion"]);
        EntityFactory::getInstance()->register(HomingSkull::class, function (World $world, CompoundTag $nbt): HomingSkull {
            return new HomingSkull(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["HomingSkull"]);
        CustomiesEntityFactory::getInstance()->registerEntity(Hades::class, "nexus:hades", function (World $world, CompoundTag $nbt): Hades {
            return new Hades(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        });
        EntityFactory::getInstance()->register(ExecutiveEnderman::class, function (World $world, CompoundTag $nbt): ExecutiveEnderman {
            return new ExecutiveEnderman(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["ExecutiveEnderman"]);
        $config = new Config($this->core->getDataFolder() . "guards.json", Config::JSON);
        foreach ($config->getAll() as $class => $locations) {
            foreach ($locations as $location) {
                $parts = explode(":", $location);
                $level = $this->core->getServer()->getWorldManager()->getWorldByName((string)$parts[3]);
                $position = new Position((float)$parts[0], (float)$parts[1], (float)$parts[2], $level);
                $this->addGuard($class, $position);
            }
        }
        $world = Server::getInstance()->getWorldManager()->getDefaultWorld();
        // All

        foreach (LevelManager::getSetup()->get("merchants") as $merchantData) {
            $merchantSpawn = LevelManager::stringToPosition($merchantData["xyz"], $world);
            $ore = self::stringToOre($merchantData["type"]);
            $spots = [];
            foreach ($merchantData["guards"] as $spot) {
                $spots[] = LevelManager::stringToPosition($spot, $world);
            }
            $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
        }

        Server::getInstance()->getWorldManager()->loadWorld(LevelManager::getSetup()->getNested("koth.world"));
        $world = Server::getInstance()->getWorldManager()->getWorldByName(LevelManager::getSetup()->getNested("koth.world"));
        $xyz1 = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("koth.xyz1"), $world);
        $xyz2 = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("koth.xyz2"), $world);
        $this->kothArena = new KothArena("KOTH Arena", $xyz1, $xyz2, 120);

//        // Coal
//        $merchantSpawn = new Position(-128.5, 78.5, -106.5, $world);
//        $ore = VanillaBlocks::COAL_ORE();
//        $spots = [
//            new Position(-132.5, 78.5, -109.5, $world),
//            new Position(-131.5, 78.5, -107.5, $world),
//            new Position(-125.5, 78.5, -107.5, $world),
//            new Position(-124.5, 78.5, -109.5, $world)
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        $merchantSpawn = new Position(-106.5, 103.5, -249.5, $world);
//        $ore = VanillaBlocks::COAL_ORE();
//        $spots = [
//            new Position(-109.5, 103.5, -245.5, $world),
//            new Position(-107.5, 103.5, -246.5, $world),
//            new Position(-107.5, 103.5, -252.5, $world),
//            new Position(-109.5, 103.5, -253.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        // Iron
//        $merchantSpawn = new Position(-13.5, 73.5, -136.5, $world);
//        $ore = VanillaBlocks::IRON_ORE();
//        $spots = [
//            new Position(-10.5, 73.5, -140.5, $world),
//            new Position(-12.5, 73.5, -139.5, $world),
//            new Position(-12.5, 73.5, -133.5, $world),
//            new Position(-10.5, 73.5, -132.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        $merchantSpawn = new Position(75.5, 83.5, -237.5, $world);
//        $ore = VanillaBlocks::IRON_ORE();
//        $spots = [
//            new Position(79.5, 83.5, -234.5, $world),
//            new Position(78.5, 83.5, -236.5, $world),
//            new Position(72.5, 83.5, -236.5, $world),
//            new Position(71.5, 83.5, -234.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        // Lapis
//        $merchantSpawn = new Position(200.5, 76.5, 23.5, $world);
//        $ore = VanillaBlocks::LAPIS_LAZULI_ORE();
//        $spots = [
//            new Position(196.5, 76.5, 20.5, $world),
//            new Position(197.5, 76.5, 22.5, $world),
//            new Position(203.5, 76.5, 22.5, $world),
//            new Position(204.5, 76.5, 20.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        $merchantSpawn = new Position(319.5, 82.5, 6.5, $world);
//        $ore = VanillaBlocks::LAPIS_LAZULI_ORE();
//        $spots = [
//            new Position(323.5, 82.5, 9.5, $world),
//            new Position(322.5, 82.5, 7.5, $world),
//            new Position(316.5, 82.5, 7.5, $world),
//            new Position(315.5, 82.5, 9.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        // Redstone
//        $merchantSpawn = new Position(147.5, 78.5, -97.5, $world);
//        $ore = VanillaBlocks::REDSTONE_ORE();
//        $spots = [
//            new Position(150.5, 78.5, -101.5, $world),
//            new Position(148.5, 78.5, -100.5, $world),
//            new Position(148.5, 78.5, -94.5, $world),
//            new Position(150.5, 78.5, -93.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        $merchantSpawn = new Position(410.5, 77.5, -184.5, $world);
//        $ore = VanillaBlocks::REDSTONE_ORE();
//        $spots = [
//            new Position(414.5, 77.5, -181.5, $world),
//            new Position(413.5, 77.5, -183.5, $world),
//            new Position(407.5, 77.5, -183.5, $world),
//            new Position(406.5, 77.5, -181.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        // Gold
//        $merchantSpawn = new Position(134.5, 110.5, 114.5, $world);
//        $ore = VanillaBlocks::GOLD_ORE();
//        $spots = [
//            new Position(130.5, 110.5, 111.5, $world),
//            new Position(131.5, 110.5, 113.5, $world),
//            new Position(137.5, 110.5, 113.5, $world),
//            new Position(138.5, 110.5, 111.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        $merchantSpawn = new Position(50.5, 89.5, 156.5, $world);
//        $ore = VanillaBlocks::GOLD_ORE();
//        $spots = [
//            new Position(47.5, 89.5, 160.5, $world),
//            new Position(49.5, 89.5, 159.5, $world),
//            new Position(49.5, 89.5, 153.5, $world),
//            new Position(47.5, 89.5, 152.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        // Diamond
//        $merchantSpawn = new Position(-188.5, 80.5, 133.5, $world);
//        $ore = VanillaBlocks::DIAMOND_ORE();
//        $spots = [
//            new Position(-191.5, 80.5, 137.5, $world),
//            new Position(-189.5, 80.5, 136.5, $world),
//            new Position(-189.5, 80.5, 130.5, $world),
//            new Position(-191.5, 80.5, 129.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        $merchantSpawn = new Position(-324.5, 71.5, 209.5, $world);
//        $ore = VanillaBlocks::DIAMOND_ORE();
//        $spots = [
//            new Position(-328.5, 71.5, 206.5, $world),
//            new Position(-327.5, 71.5, 208.5, $world),
//            new Position(-321.5, 71.5, 208.5, $world),
//            new Position(-320.5, 71.5, 206.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        // Emerald
//        $merchantSpawn = new Position(31.5, 66.5, 418.5, $world);
//        $ore = VanillaBlocks::EMERALD_ORE();
//        $spots = [
//            new Position(35.5, 66.5, 421.5, $world),
//            new Position(34.5, 66.5, 419.5, $world),
//            new Position(28.5, 66.5, 419.5, $world),
//            new Position(27.5, 66.5, 421.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
//        $merchantSpawn = new Position(149.5, 79.5, 306.5, $world);
//        $ore = VanillaBlocks::EMERALD_ORE();
//        $spots = [
//            new Position(145.5, 79.5, 303.5, $world),
//            new Position(146.5, 79.5, 305.5, $world),
//            new Position(152.5, 79.5, 305.5, $world),
//            new Position(153.5, 79.5, 303.5, $world),
//        ];
//        $this->merchantShops[] = new MerchantShop($merchantSpawn, $ore, $spots);
    }

    private static function stringToOre(string $ore): Block
    {
        return match ($ore) {
            "iron" => VanillaBlocks::IRON_ORE(),
            "gold" => VanillaBlocks::GOLD_ORE(),
            "diamond" => VanillaBlocks::DIAMOND_ORE(),
            "emerald" => VanillaBlocks::EMERALD_ORE(),
            "redstone" => VanillaBlocks::REDSTONE_ORE(),
            "lapis" => VanillaBlocks::LAPIS_LAZULI_ORE(),
            default => VanillaBlocks::COAL_ORE()
        };
    }


    /**
     * @param bool $heroic
     */
    public function spawnMerchantShop(bool $heroic = false): void
    {
        $shops = $this->merchantShops;
        foreach ($this->usedShops as $shop) {
            unset($shops[$shop]);
        }
        if (empty($shops)) {
            return;
        }
        $key = array_rand($shops);
        $shop = $shops[$key];
        $this->usedShops[$key] = $key;
        $shop->spawnShop($key, $heroic);
    }

    /**
     * @param int $id
     * @param bool $death
     */
    public function resetMerchantShop(int $id, bool $death = false): void
    {
        $valid = true;
        if (!$death)
            $valid = $this->merchantShops[$id]->resetTempData();
        if ($valid)
            unset($this->usedShops[$id]);
    }

    public function resetMerchantShops(): void
    {
        foreach ($this->usedShops as $id) {
            $this->resetMerchantShop($id);
            //$this->merchantShops[$id]->resetTempData();
        }
    }

    /**
     * @return KOTHArena|null
     */
    public function getKOTHGame(): ?KOTHArena
    {
        return $this->kothGame;
    }

    public function initiateKOTHGame(): void
    {
        $this->kothGame = $this->kothArena;
    }

    /**
     * @throws TranslationException
     */
    public function startKOTHGame(): void
    {
        if ($this->kothGame !== null) {
            $this->kothGame->setStarted(true);
        }
        $this->core->getServer()->broadcastMessage(Translation::getMessage("kothBegin"));
    }

    public function endKOTHGame(): void
    {
        $this->kothGame = null;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return int
     */
    public function getEnderPearlCooldown(NexusPlayer $player): int
    {
        $cd = -1;
        if (isset($this->listener->enderPearlCooldown[$player->getUniqueId()->toString()])) {
            if ((10 - (time() - $this->listener->enderPearlCooldown[$player->getUniqueId()->toString()])) >= 0) {
                $cd = 10 - (time() - $this->listener->enderPearlCooldown[$player->getUniqueId()->toString()]);
            }
        }
        return $cd;
    }

    /**
     * @param NexusPlayer $player
     * @param int $cooldown
     */
    public function setEnderPearlCooldown(NexusPlayer $player, int $cooldown = 10)
    {
        $this->listener->enderPearlCooldown[$player->getUniqueId()->toString()] = time() - (10 - $cooldown);
    }

    /**
     * @param string $class
     * @param Position $position
     */
    public function addGuard(string $class, Position $position): void
    {
        $guard = new Guard($class, $position);
        $this->guards[$guard->getEntityId()] = $guard;
        $this->saveGuards();
    }

    /**
     * @param Guard $guard
     */
    public function removeGuard(Guard $guard): void
    {
        unset($this->guards[$guard->getEntityId()]);
        $guard->despawnFromAll();
        $this->saveGuards();
    }

    public function saveGuards(): void
    {
        $guards = [];
        foreach ($this->guards as $guard) {
            $station = $guard->getStation();
            $guards[$guard->getClass()][] = $station->getX() . ":" . $station->getY() . ":" . $station->getZ() . ":" . $station->getWorld()->getFolderName();
        }
        $config = new Config($this->core->getDataFolder() . "guards.json", Config::JSON);
        foreach ($guards as $class => $list) {
            $config->set($class, $list);
        }
        $config->save();
    }

    /**
     * @return Guard[]
     */
    public function getGuards(): array
    {
        return $this->guards;
    }

    /**
     * @param int $entityId
     *
     * @return Guard|null
     */
    public function getGuard(int $entityId): ?Guard
    {
        return $this->guards[$entityId] ?? null;
    }
}