<?php

namespace core\level;

use core\command\forms\BankBlockMainForm;
use core\command\inventory\EnchanterInventory;
use core\command\inventory\LootboxInventory;
use core\command\inventory\PrestigePickaxeInventory;
use core\command\inventory\ShopListInventory;
use core\command\inventory\TinkerInventory;
use core\command\inventory\WarpMenuInventory;
use core\display\animation\entity\SlotBotEntity;
use core\game\item\types\vanilla\Pickaxe;
use core\game\plots\command\inventory\PlotMenuInventory;
use core\game\quest\inventory\QuestShopInventory;
use core\level\block\BuildingBlock;
use core\level\block\Meteor;
use core\level\block\Meteorite;
use core\level\block\PrismarineOre;
use core\level\block\RushOreBlock;
use core\level\entity\types\FireworksRocket;
use core\level\block\CoalOre;
use core\level\block\CrudeOre;
use core\level\block\DiamondOre;
use core\level\block\EmeraldOre;
use core\level\block\GoldOre;
use core\level\block\IronOre;
use core\level\block\LapisOre;
use core\level\block\OreGenerator;
use core\level\block\RedstoneOre;
use core\level\entity\EntityListener;
use core\level\entity\npc\NPC;
use core\level\entity\npc\NPCListener;
use core\level\entity\types\Lightning;
use core\level\entity\types\Powerball;
use core\level\entity\types\PummelBlock;
use core\level\task\ExplosionQueueTask;
use core\level\task\SpawnMeteoriteTask;
use core\level\task\SpawnMeteorTask;
use core\level\task\SpawnRushOreTask;
use core\Nexus;
use core\player\NexusPlayer;
use customiesdevs\customies\block\CustomiesBlockFactory;
use customiesdevs\customies\block\Material;
use customiesdevs\customies\block\Model;
use libs\utils\Utils;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockIdentifierFlattened;
use pocketmine\block\BlockLegacyIds as Ids;
use pocketmine\block\BlockToolType;
use pocketmine\block\Furnace;
use pocketmine\block\Note;
use pocketmine\block\Opaque;
use pocketmine\block\tile\TileFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\item\ItemIds;
use pocketmine\item\ToolTier;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;

class LevelManager {


    public static $blockBreaks = [];
    /** @var Nexus */
    private $core;

    /** @var ExplosionQueueTask */
    private $explosionQueue;

    /** @var SpawnMeteorTask */
    private $meteorSpawner;

    /** @var NPC[] */
    private $npcs = [];

    /** @var Config */
    private static $setup;

    /**
     * LevelManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        self::$setup = new Config($this->core->getDataFolder() . "setup.yml", Config::YAML);
        $this->init();
        $this->explosionQueue = new ExplosionQueueTask();
        $core->getScheduler()->scheduleRepeatingTask($this->explosionQueue, 1);
        $core->getScheduler()->scheduleRepeatingTask(new SpawnMeteoriteTask($this), 20);
        $core->getScheduler()->scheduleRepeatingTask(new SpawnRushOreTask($this), 20);
        $this->meteorSpawner = new SpawnMeteorTask($this);
        $core->getScheduler()->scheduleRepeatingTask($this->meteorSpawner, 20);
        $core->getServer()->getPluginManager()->registerEvents(new LevelListener($core), $core);
        $core->getServer()->getPluginManager()->registerEvents(new EntityListener($core), $core);
        $core->getServer()->getPluginManager()->registerEvents(new NPCListener($core), $core);
        // TODO ?
//        $minX = -300;
//        $maxX = 400;
//        $minZ = -300;
//        $maxZ = 400;
//        $world = $core->getServer()->getWorldManager()->getDefaultWorld();
//        $core->getLogger()->notice("Registering chunk loaders for all Overworld chunks");
//        for($i = $minX; $i <= $maxX; $i += 16) {
//            for($j = $minZ; $j <= $maxZ; $j += 16) {
//                $x = $i >> 4;
//                $z = $j >> 4;
//                $world->registerChunkLoader(new FakeChunkLoader($x, $z), $x, $z);
//            }
//        }
    }

    /**
     * @return Config
     */
    public static function getSetup(): Config
    {
        return self::$setup;
    }

    public static function stringToPosition(string $arg, World $level) : Position {
        $xyz = explode(":", $arg);
        return new Position((float)$xyz[0], (float)$xyz[1], (float)$xyz[2], $level);
    }

    public function init(): void {
        /** @var TileFactory $tileFactory */
        $tileFactory = TileFactory::getInstance();
        $tileFactory->register(tile\Meteor::class);
        $tileFactory->register(tile\Meteorite::class);
        $tileFactory->register(tile\CrudeOre::class);
        $tileFactory->register(tile\OreGenerator::class);
        $tileFactory->register(tile\RushOreBlock::class);
        /** @var BlockFactory $blockFactory */
        $blockFactory = BlockFactory::getInstance();
        $blockFactory->register(new CoalOre(new BID(Ids::COAL_ORE, 0), "Coal Ore"), true);
        $blockFactory->register(new IronOre(new BID(Ids::IRON_ORE, 0), "Iron Ore"), true);
        $blockFactory->register(new LapisOre(new BID(Ids::LAPIS_ORE, 0), "Lapis Lazuli Ore"), true);
        $blockFactory->register(new RedstoneOre(new BlockIdentifierFlattened(Ids::REDSTONE_ORE, [Ids::GLOWING_REDSTONE_ORE], 0), "Redstone Ore"), true);
        $blockFactory->register(new GoldOre(new BID(Ids::GOLD_ORE, 0), "Gold Ore"), true);
        $blockFactory->register(new DiamondOre(new BID(Ids::DIAMOND_ORE, 0), "Diamond Ore"), true);
        $blockFactory->register(new EmeraldOre(new BID(Ids::EMERALD_ORE, 0), "Emerald Ore"), true);
        $blockFactory->register(new PrismarineOre(new BID(Ids::PRISMARINE, 0), "Prismarine Ore"), true);
        $blockFactory->register(new Meteor(new BID(Ids::CORAL_BLOCK, 9, ItemIds::CORAL_BLOCK, tile\Meteor::class), "Meteor"), true);
        $blockFactory->register(new Meteorite(new BID(Ids::MAGMA, 0, ItemIds::MAGMA, tile\Meteorite::class), "Meteorite"), true);
        $blockFactory->register(new CrudeOre(new BID(Ids::NETHER_QUARTZ_ORE, 0, ItemIds::NETHER_QUARTZ_ORE, tile\CrudeOre::class), "Crude Ore"), true);
        $blockFactory->register(new OreGenerator(new BID(Ids::COAL_BLOCK, 0, ItemIds::COAL_BLOCK, tile\OreGenerator::class), "Coal Block"), true);
        $blockFactory->register(new OreGenerator(new BID(Ids::IRON_BLOCK, 0, ItemIds::IRON_BLOCK, tile\OreGenerator::class), "Iron Block"), true);
        $blockFactory->register(new OreGenerator(new BID(Ids::LAPIS_BLOCK, 0, ItemIds::LAPIS_BLOCK, tile\OreGenerator::class), "Lapis Lazuli Block"), true);
        $blockFactory->register(new OreGenerator(new BID(Ids::REDSTONE_BLOCK, 0, ItemIds::REDSTONE_BLOCK, tile\OreGenerator::class), "Redstone Block"), true);
        $blockFactory->register(new OreGenerator(new BID(Ids::GOLD_BLOCK, 0, ItemIds::GOLD_BLOCK, tile\OreGenerator::class), "Gold Block"), true);
        $blockFactory->register(new OreGenerator(new BID(Ids::DIAMOND_BLOCK, 0, ItemIds::DIAMOND_BLOCK, tile\OreGenerator::class), "Diamond Block"), true);
        $blockFactory->register(new OreGenerator(new BID(Ids::EMERALD_BLOCK, 0, ItemIds::EMERALD_BLOCK, tile\OreGenerator::class), "Emerald Block"), true);

        $blockFactory->register(new RushOreBlock(new BID(Ids::GRAY_GLAZED_TERRACOTTA, 0, null, tile\RushOreBlock::class), "Coal Ore Rush", new BlockBreakInfo(2.0, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel())), true);
        $blockFactory->register(new RushOreBlock(new BID(Ids::SILVER_GLAZED_TERRACOTTA, 0, null, tile\RushOreBlock::class), "Iron Ore Rush", new BlockBreakInfo(3.0, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel())), true);
        $blockFactory->register(new RushOreBlock(new BID(Ids::BLUE_GLAZED_TERRACOTTA, 0, null, tile\RushOreBlock::class), "Lapis Ore Rush", new BlockBreakInfo(3.0, BlockToolType::PICKAXE, ToolTier::GOLD()->getHarvestLevel())), true);
        $blockFactory->register(new RushOreBlock(new BID(Ids::RED_GLAZED_TERRACOTTA, 0, null, tile\RushOreBlock::class), "Redstone Ore Rush", new BlockBreakInfo(3.0, BlockToolType::PICKAXE, ToolTier::GOLD()->getHarvestLevel())), true);
        $blockFactory->register(new RushOreBlock(new BID(Ids::YELLOW_GLAZED_TERRACOTTA, 0, null, tile\RushOreBlock::class), "Gold Ore Rush", new BlockBreakInfo(3.0, BlockToolType::PICKAXE, ToolTier::IRON()->getHarvestLevel())), true);
        $blockFactory->register(new RushOreBlock(new BID(Ids::LIGHT_BLUE_GLAZED_TERRACOTTA, 0, null, tile\RushOreBlock::class), "Diamond Ore Rush", new BlockBreakInfo(3.0, BlockToolType::PICKAXE, ToolTier::IRON()->getHarvestLevel())), true);
        $blockFactory->register(new RushOreBlock(new BID(Ids::GREEN_GLAZED_TERRACOTTA, 0, null, tile\RushOreBlock::class), "Emerald Ore Rush", new BlockBreakInfo(3.0, BlockToolType::PICKAXE, ToolTier::DIAMOND()->getHarvestLevel())), true);

        $blockFactory->register(new BuildingBlock(new BID(Ids::END_STONE, 0, ItemIds::END_STONE), "Building Block", new BlockBreakInfo(5.0, BlockToolType::PICKAXE, ToolTier::DIAMOND()->getHarvestLevel())), true);
        /** @var EntityFactory $entityFactory */
        $entityFactory = EntityFactory::getInstance();
        $entityFactory->register(Lightning::class, function(World $world, CompoundTag $nbt): Lightning {
            return new Lightning(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['Lightning', 'minecraft:lightning']);
        $entityFactory->register(FireworksRocket::class, function(World $world, CompoundTag $nbt): FireworksRocket {
            return new FireworksRocket(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        },  ['Lightning', 'minecraft:fireworks_rocket']);
        $entityFactory->register(PummelBlock::class, function(World $world, CompoundTag $nbt): PummelBlock {
            return new PummelBlock(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['PummelBlock']);
        $entityFactory->register(Powerball::class, function(World $world, CompoundTag $nbt): Powerball {
            return new Powerball(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['Powerball']);
        $entityFactory->register(SlotBotEntity::class, function(World $world, CompoundTag $nbt) : SlotBotEntity {
            return new SlotBotEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['SlotBotEntity']);
        $level = $this->core->getServer()->getWorldManager()->getDefaultWorld();
        $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR;
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "enchanter.png")), self::stringToPosition(self::getSetup()->getNested("npc.enchanter"), $level), TextFormat::BOLD . TextFormat::DARK_PURPLE . "Enchanter\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            (new EnchanterInventory())->send($player);
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "prestige_pickaxe.png")), self::stringToPosition(self::getSetup()->getNested("npc.prestige-pickaxe"), $level), TextFormat::BOLD . TextFormat::AQUA . "Pickaxe Prestige\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            $item = $player->getInventory()->getItemInHand();
            if($item instanceof Pickaxe) {
                (new PrestigePickaxeInventory($player, $item))->send($player);
            }
            else {
                $player->playErrorSound();
                $player->sendTranslatedMessage("invalidItem");
            }
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "tinkerer.png")), self::stringToPosition(self::getSetup()->getNested("npc.tinkerer"), $level), TextFormat::BOLD . TextFormat::DARK_AQUA . "Tinkerer\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            (new TinkerInventory())->send($player);
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "ore_exchanger.png")), self::stringToPosition(self::getSetup()->getNested("npc.ore-exchanger"), $level), TextFormat::BOLD . TextFormat::GOLD . "Ore Exchanger\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            $place = Nexus::getInstance()->getGameManager()->getEconomyManager()->getPlace("Ores");
            (new ShopListInventory($place))->send($player);
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "ore_generators.png")), self::stringToPosition(self::getSetup()->getNested("npc.ore-generator"), $level), TextFormat::BOLD . TextFormat::GOLD . "Ore Generators\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            $place = Nexus::getInstance()->getGameManager()->getEconomyManager()->getPlace("Ore Generators");
            (new ShopListInventory($place))->send($player);
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "blacksmith.png")), self::stringToPosition(self::getSetup()->getNested("npc.blacksmith"), $level), TextFormat::BOLD . TextFormat::GOLD . "Blacksmith\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            $place = Nexus::getInstance()->getGameManager()->getEconomyManager()->getPlace("Blacksmith");
            (new ShopListInventory($place))->send($player);
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "weaver.png")), self::stringToPosition(self::getSetup()->getNested("npc.weaver"), $level), TextFormat::BOLD . TextFormat::GOLD . "Weaver\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            $place = Nexus::getInstance()->getGameManager()->getEconomyManager()->getPlace("Weaver");
            (new ShopListInventory($place))->send($player);
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "craftsman.png")), self::stringToPosition(self::getSetup()->getNested("npc.craftsman"), $level), TextFormat::BOLD . TextFormat::GOLD . "Craftsman\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            $place = Nexus::getInstance()->getGameManager()->getEconomyManager()->getPlace("Craftsman");
            (new ShopListInventory($place))->send($player);
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "carpenter.png")), self::stringToPosition(self::getSetup()->getNested("npc.carpenter"), $level), TextFormat::BOLD . TextFormat::GOLD . "Carpenter\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            $place = Nexus::getInstance()->getGameManager()->getEconomyManager()->getPlace("Carpenter");
            (new ShopListInventory($place))->send($player);
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "mines.png")), self::stringToPosition(self::getSetup()->getNested("npc.mines"), $level), TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "Mines\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            $warps = new WarpMenuInventory($player);
            $warps->send($player);
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "plots.png")), self::stringToPosition(self::getSetup()->getNested("npc.plots"), $level), TextFormat::BOLD . TextFormat::AQUA . "Plots\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            $plot = new PlotMenuInventory();
            $plot->send($player);
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "banker.png")), self::stringToPosition(self::getSetup()->getNested("npc.banker"), $level), TextFormat::BOLD . TextFormat::GREEN . "Bank Blocks\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            if($player->isLoaded()) {
                $player->sendForm(new BankBlockMainForm($player));
            }
        }));
        $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "lootbox_merchant.png")), self::stringToPosition(self::getSetup()->getNested("npc.lootbox-merchant"), $level), TextFormat::BOLD . TextFormat::AQUA . "Lootbox Merchant\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
            $lb = new LootboxInventory();
            $lb->send($player);
        }));
        // Sort of an NPC
        $sP = self::stringToPosition(self::getSetup()->getNested("npc.slotbot"), $level);
        $slotbot = new SlotBotEntity(new Location($sP->getX(), $sP->getY(), $sP->getZ(), Server::getInstance()->getWorldManager()->getDefaultWorld(), 0, 0));
        $slotbot->spawnToAll();
        if(date("l") === "Friday") { // TODO: learn this for events
            $this->addNPC(new NPC(Utils::createSkin(Utils::getSkinDataFromPNG($path . "json" . DIRECTORY_SEPARATOR . "mystery_man.png")), self::stringToPosition(self::getSetup()->getNested("npc.mystery-man"), $level), TextFormat::BOLD . TextFormat::DARK_AQUA . "Mystery Man\n" . TextFormat::GRAY . "(Click)", function(NexusPlayer $player) {
                $items = $this->core->getGameManager()->getQuestManager()->getActiveItems();
                $shop = new QuestShopInventory($items);
                $shop->send($player);
            }));
        }
    }

    /**
     * @param World $level
     * @param AxisAlignedBB $bb
     *
     * @return array
     */
    public static function getNearbyTiles(World $level, AxisAlignedBB $bb): array {
        $nearby = [];
        $minX = ((int)floor($bb->minX - 2)) >> 4;
        $maxX = ((int)floor($bb->maxX + 2)) >> 4;
        $minZ = ((int)floor($bb->minZ - 2)) >> 4;
        $maxZ = ((int)floor($bb->maxZ + 2)) >> 4;
        for($x = $minX; $x <= $maxX; ++$x) {
            for($z = $minZ; $z <= $maxZ; ++$z) {
                if(!$level->isChunkLoaded($x, $z)){
                    continue;
                }
                foreach($level->getChunk($x, $z)->getTiles() as $ent) {
                    $bbs = $ent->getBlock()->getCollisionBoxes();
                    if(count($bbs) !== 1) {
                        continue;
                    }
                    $entbb = $bbs[0];
                    if($entbb !== null) {
                        if($entbb->intersectsWith($bb)) {
                            $nearby[] = $ent;
                        }
                    }
                }
            }
        }
        return $nearby;
    }

    /**
     * @return ExplosionQueueTask
     */
    public function getExplosionQueue(): ExplosionQueueTask {
        return $this->explosionQueue;
    }

    /**
     * @return SpawnMeteorTask
     */
    public function getMeteorSpawner(): SpawnMeteorTask {
        return $this->meteorSpawner;
    }

    /**
     * @return NPC[]
     */
    public function getNPCs(): array {
        return $this->npcs;
    }

    /**
     * @param int $entityId
     *
     * @return NPC|null
     */
    public function getNPC(int $entityId): ?NPC {
        return $this->npcs[$entityId] ?? null;
    }

    /**
     * @param NPC $npc
     */
    public function addNPC(NPC $npc): void {
        $this->npcs[$npc->getEntityId()] = $npc;
    }

    /**
     * @param NPC $npc
     */
    public function removeNPC(NPC $npc): void {
        unset($this->npcs[$npc->getEntityId()]);
    }
}