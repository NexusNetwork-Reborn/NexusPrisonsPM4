<?php
declare(strict_types=1);

namespace core;

use core\display\animation\entity\SlotBotEntity;
use core\display\DisplayManager;
use core\command\CommandManager;
use core\game\badlands\bandit\BaseBandit;
use core\game\combat\merchants\BodyGuard;
use core\game\combat\merchants\OreMerchant;
use core\game\GameManager;
use core\level\block\RushOreBlock;
use core\level\LevelManager;
use core\level\task\SpawnRushOreTask;
use core\player\NexusPlayer;
use core\player\PlayerManager;
use core\provider\MySQLProvider;
use core\server\ServerManager;
use core\translation\Translation;
use core\translation\TranslationException;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\entity\Living;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\WorldCreationOptions;
use xenialdan\apibossbar\API;

class Nexus extends PluginBase {

    const GAMEMODE = "Prisons";

    const SERVER_NAME = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "NeXus" . TextFormat::GOLD . self::GAMEMODE;

    const EXTRA_SLOTS = 100;

    public const SUPER_ADMIN = ["THeRuTHLessCoW", "KingOfTurkey38", "dEmONicGG", "IcyEndymion004", "Imtheunknown121"];

    const START = 1693674687; // Celestial Start
    //const START = 1640074467; // OLD: 1629561600

    const PLANET = "Celestial Planet";

    /** @var self */
    private static $instance;

    /** @var BigEndianNbtSerializer */
    private static $nbtWriter;

    /** @var bool */
    private $loaded = false;

    /** @var bool */
    private $globalMute = false;

    /** @var MySQLProvider */
    private $mySQLProvider;

    /** @var ServerManager */
    private $serverManager;

    /** @var PlayerManager */
    private $playerManager;

    /** @var CommandManager */
    private $commandManager;

    /** @var LevelManager */
    private $levelManager;

    /** @var GameManager */
    private $gameManager;

    /** @var DisplayManager */
    private $displayManager;

    /** @var ZippedResourcePack $pack */
    //private $pack;

    /**
     * @return Nexus
     */
    public static function getInstance(): self {
        return self::$instance;
    }

    /**
     * @param Item $item
     *
     * @return string
     */
    public static function encodeItem(Item $item): string {
        return self::$nbtWriter->write(new TreeRoot($item->nbtSerialize()));
    }

    /**
     * @param string $compression
     *
     * @return Item
     *
     * @throws PluginException
     */
    public static function decodeItem(string $compression): Item {
        $root = self::$nbtWriter->read($compression);
        $tag = $root->mustGetCompoundTag();
        return Item::nbtDeserialize($tag);
    }

    /**
     * @param Inventory $inventory
     * @param bool $includeEmpty
     *
     * @return string
     */
    public static function encodeInventory(Inventory $inventory, bool $includeEmpty = false): string {
        $contents = $inventory->getContents($includeEmpty);
        return self::encodeItems($contents);
    }

    /**
     * @param Item[] $contents
     *
     * @return string
     */
    public static function encodeItems(array $contents): string {
        $items = [];
        foreach($contents as $item) {
            $items[] = $item->nbtSerialize();
        }
        $compound = new CompoundTag();
        $compound->setTag("Items", new ListTag($items));
        return self::$nbtWriter->write(new TreeRoot($compound));
    }

    /**
     * @param string|null $compression
     *
     * @return array
     */
    public static function decodeInventory(?string $compression): array {
        if(empty($compression)) {
            return [];
        }
        $root = self::$nbtWriter->read($compression);
        $tag = $root->mustGetCompoundTag();
        $items = $tag->getListTag("Items");
        $content = [];
        /** @var CompoundTag $item */
        foreach($items->getValue() as $item) {
            $content[] = Item::nbtDeserialize($item);
        }
        return $content;
    }

    /**
     * @param string $identifier
     * @param array $parameters
     */
    public static function broadcastTranslatedMessage(string $identifier, array $parameters = []): void {
        try {
            Server::getInstance()->broadcastMessage(Translation::getMessage($identifier, $parameters));
        }
        catch(TranslationException $exception) {
            Server::getInstance()->broadcastMessage($exception->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function isGlobalMuted(): bool {
        return $this->globalMute;
    }

    /**
     * @param bool $globalMute
     */
    public function setGlobalMute(bool $globalMute): void {
        $this->globalMute = $globalMute;
    }

    /**
     * @return bool
     */
    public static function isDebugging(): bool {
        return true;
    }

    public function onLoad(): void  {
        self::$nbtWriter = new BigEndianNbtSerializer();
        self::$instance = $this;
        $this->getServer()->getNetwork()->setName(self::SERVER_NAME);
        @mkdir($this->getDataFolder());
        $this->saveResource("guards.json");
        $this->saveResource("lottery.json");
        $this->saveResource("nicks.json");
        $this->saveResource("redeemed.json");
        $this->saveResource("skindata.json");
        $this->saveResource("plots.json");
        $this->saveResource("fund.json");
        $this->saveResource("setup.yml");

//        $this->saveResource("nexusPE.mcpack", true);
//        $manager = $this->getServer()->getResourcePackManager();
//        try {
//            $pack = new ZippedResourcePack($this->getDataFolder() . "NexusPE.mcpack");
//        } catch (\Exception $e) {
//            $this->getLogger()->error("Could not load resource pack: " . $e->getMessage());
//            return;
//        }
//        $reflection = new \ReflectionClass($manager);
//        $property = $reflection->getProperty("resourcePacks");
//        $property->setAccessible(true);
//        $currentResourcePacks = $property->getValue($manager);
//        $currentResourcePacks[] = $pack;
//        $property->setValue($manager, $currentResourcePacks);
//        $property = $reflection->getProperty("uuidList");
//        $property->setAccessible(true);
//        $currentUUIDPacks = $property->getValue($manager);
//        $currentUUIDPacks[strtolower($pack->getPackId())] = $pack;
//        $property->setValue($manager, $currentUUIDPacks);
//        $property = $reflection->getProperty("serverForceResources");
//        $property->setAccessible(true);
//        $property->setValue($manager, true);
    }

    public function onEnable(): void {
        if(!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
        API::load($this);
        $worlds = ["lounge", "citizen", "merchant", "king", "executive", "boss", "koth", "badlands", "badlandstest", "pvp", "snowman_koth", "spawn"];
        $wm = Server::getInstance()->getWorldManager();
        foreach ($worlds as $world){
            if ($wm->getWorldByName($world) === null) {
                $options = WorldCreationOptions::create();
                $wm->generateWorld($world, $options);
            }
            $wm->loadWorld($world);
        }
        foreach($this->getServer()->getWorldManager()->getWorlds() as $level) {
            $level->setTime(0);
            $level->stopTime();
        }
        $time = microtime(true);
        $this->getServer()->getPluginManager()->registerEvents(new NexusListener($this), $this);
        $this->mySQLProvider = new MySQLProvider($this);
        $this->getLogger()->info("Loaded MySQL Provider in: " . number_format(microtime(true) - $time, 3) . "s");
        $time = microtime(true);
        $this->levelManager = new LevelManager($this);
        $this->getLogger()->info("Loaded Level Manager in: " . number_format(microtime(true) - $time, 3) . "s");
        $time = microtime(true);
        $this->serverManager = new ServerManager($this);
        $this->getLogger()->info("Loaded Server Manager in: " . number_format(microtime(true) - $time, 3) . "s");
        $time = microtime(true);
        $this->gameManager = new GameManager($this);
        $this->gameManager->getQuestManager()->initQuestShopItems();
        $this->gameManager->getBlackAuctionManager()->initItemPool();
        $this->getLogger()->info("Loaded Game Manager in: " . number_format(microtime(true) - $time, 3) . "s");
        $time = microtime(true);
        $this->playerManager = new PlayerManager($this);
        $this->getLogger()->info("Loaded Player Manager in: " . number_format(microtime(true) - $time, 3) . "s");
        $time = microtime(true);
        $this->commandManager = new CommandManager($this);
        $this->getLogger()->info("Loaded Command Manager in: " . number_format(microtime(true) - $time, 3) . "s");
        $time = microtime(true);
        $this->displayManager = new DisplayManager($this);
        $this->getLogger()->info("Loaded Display Manager in: " . number_format(microtime(true) - $time, 3) . "s");
        $this->loaded = true;
        foreach ($this->gameManager->getZoneManager()->getBadlands() as $badland){
            $world = $badland->getLevel();
            foreach($world->getEntities() as $entity){
                if (!$entity instanceof NexusPlayer){
                    $entity->despawnFromAll();
                }
            }
        }
    }

    public function onDisable(): void  {
        $world = $this->getServer()->getWorldManager()->getDefaultWorld();
        foreach (SpawnRushOreTask::$rushes as $pos) {
            $block = $world->getBlock($pos);
            if($block instanceof RushOreBlock) {
                $world->setBlock($pos, $block->getNormalOreBlock());
                if(($tile = $world->getTile($pos)) !== null) {
                    $world->removeTile($tile);
                }
            }
        }
        $this->getScheduler()->cancelAllTasks();
        foreach($this->playerManager->getGangManager()->getGangs() as $gang) {
            if($gang->needsUpdate()) {
                $gang->update();
            }
        }
        foreach($this->gameManager->getPlotManager()->getPlots() as $plot) {
            $owner = $plot->getOwner();
            if($owner !== null) {
                if($owner->needsUpdate()) {
                    $owner->update();
                }
            }
        }
        foreach($this->getServer()->getWorldManager()->getWorlds() as $level) {
            foreach($level->getEntities() as $entity) {
                if($entity instanceof OreMerchant or $entity instanceof BodyGuard or $entity instanceof SlotBotEntity) {
                    $entity->close();
                }
            }
            $level->save(true);
        }
        foreach($this->getServer()->getOnlinePlayers() as $player) {
            if(!$player instanceof NexusPlayer) {
                continue;
            }
            if($player->isTagged()) {
                $player->combatTag(false);
            }
            //if(Nexus::getInstance()->getServer()->getPort() === 19123) {
                //$player->transfer("test.aethic.games", 19132);
                //continue;
            //}
            $player->transfer("play.nexuspe.net", 19132, TextFormat::RESET . TextFormat::RED . "Server is restarting...");
        }
        //$this->gameManager->getCombatManager()->saveGuards();
        //$this->gameManager->getPlotManager()->savePlots();
        $this->playerManager->saveNicknames();
        $this->gameManager->getGambleManager()->saveLottery();
        $this->gameManager->getItemManager()->saveRedeemed();
        $this->gameManager->getFundManager()->saveUnlocked();
        $active = $this->gameManager->getBlackAuctionManager()->getActiveAuction();
        if($active !== null) {
            $active->sell();
        }
//        $manager = $this->getServer()->getResourcePackManager();
//        $pack = $this->pack;
//        $reflection = new \ReflectionClass($manager);
//        $property = $reflection->getProperty("resourcePacks");
//        $property->setAccessible(true);
//        $currentResourcePacks = $property->getValue($manager);
//        $key = array_search($pack, $currentResourcePacks);
//        if($key !== false){
//            unset($currentResourcePacks[$key]);
//            $property->setValue($manager, $currentResourcePacks);
//        }
//        $property = $reflection->getProperty("uuidList");
//        $property->setAccessible(true);
//        $currentUUIDPacks = $property->getValue($manager);
//        if(isset($currentResourcePacks[strtolower($pack->getPackId())])) {
//            unset($currentUUIDPacks[strtolower($pack->getPackId())]);
//            $property->setValue($manager, $currentUUIDPacks);
//            unlink($this->getDataFolder() . "NexusPE.mcpack");
//        }
    }

    /**
     * @return MySQLProvider
     */
    public function getMySQLProvider(): MySQLProvider {
        return $this->mySQLProvider;
    }

    /**
     * @return LevelManager
     */
    public function getLevelManager(): LevelManager {
        return $this->levelManager;
    }

    /**
     * @return ServerManager
     */
    public function getServerManager(): ServerManager {
        return $this->serverManager;
    }

    /**
     * @return CommandManager
     */
    public function getCommandManager(): CommandManager {
        return $this->commandManager;
    }

    /**
     * @return PlayerManager
     */
    public function getPlayerManager(): PlayerManager {
        return $this->playerManager;
    }

    /**
     * @return null|GameManager
     */
    public function getGameManager(): ?GameManager {
        return $this->gameManager;
    }

    /**
     * @return DisplayManager
     */
    public function getDisplayManager(): DisplayManager {
        return $this->displayManager;
    }
}