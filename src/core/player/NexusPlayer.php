<?php
declare(strict_types=1);

namespace core\player;

use core\game\combat\guards\BaseGuard;
use core\game\combat\task\FakeDropsTask;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\trinket\entity\GrapplingHook;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\XPBottle;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Sword;
use core\game\quest\Quest;
use core\game\zone\Zone;
use core\game\zone\ZoneListener;
use core\game\zone\ZoneManager;
use core\level\block\Ore;
use core\level\LevelException;
use core\level\LevelManager;
use core\level\task\SpawnRushOreTask;
use core\level\tile\RushOreBlock;
use core\player\gang\Gang;
use core\Nexus;
use core\player\handler\SurvivalBlockBreakHandler;
use core\player\rank\Rank;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use core\player\task\ExecutiveMineHeartbeatTask;
use core\player\task\LoadPlayerTask;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\BossBar;
use libs\utils\FloatingTextParticle;
use libs\utils\Scoreboard;
use libs\utils\Task;
use libs\utils\Utils;
use libs\utils\UtilsException;
use muqsit\invmenu\InvMenu;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\animation\CriticalHitAnimation;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\form\Form;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\MeleeWeaponEnchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Totem;
use pocketmine\lang\Translatable;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\permission\Permission;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\EntityAttackNoDamageSound;
use pocketmine\world\sound\EntityAttackSound;
use pocketmine\world\sound\FireExtinguishSound;
use pocketmine\world\sound\ItemBreakSound;

class NexusPlayer extends Player {

    const PUBLIC = 0;
    const GANG = 1;
    const ALLY = 2;
    const STAFF = 3;

    const TELEPORT = "Teleport";
    const SPAWN_FLARE = "Spawn_Flare";
    const POWERBALL = "Powerball";

    /** @var int */
    public $cps = 0;

    /** @var int */
    private $loadTime = 0;

    /** @var Scoreboard */
    private $scoreboard;

    /** @var BossBar */
    private $bossBar;

    /** @var null|CommandSender */
    private $lastTalked = null;

    /** @var bool */
    private $vanish = false;

    /** @var Nexus */
    private $core;

    /** @var bool */
    private $runningCrateAnimation = false;

    /** @var bool */
    private $voteChecking = false;

    /** @var bool */
    private $breaking = false;

    /** @var null|Block */
    private $block = null;

    /** @var bool */
    private $voted = false;

    /** @var bool */
    private $teleporting = false;

    /** @var bool */
    private $frozen = false;

    /** @var int */
    private $chatMode = self::PUBLIC;

    /** @var int */
    private $combatTag = 0;

    /** @var FloatingTextParticle[] */
    public $floatingTexts = [];

    /** @var int[] */
    private $alerts = [];

    /** @var int[] */
    private $cePopups = [];

    /** @var null|DataSession */
    private $dataSession = null;

    /** @var null|CESession */
    private $ceSession = null;

    /** @var bool */
    private $disguise = false;

    /** @var null|Rank */
    private $disguiseRank = null;

    /** @var null|Zone */
    private $zone = null;

    /** @var null|GrapplingHook */
    private $grapplingHook = null;

    /** @var int[] */
    private $teleportRequests = [];

    /** @var int[] */
    private $tradeRequests = [];

    /** @var int */
    private $guardTax = 0;

    /** @var int */
    private $lastJet = 0;

    /** @var bool */
    private $usingJet = false;

    /** @var bool */
    private $takeFallDamage = true;

    /** @var int[] */
    private $cooldowns = [];

    /** @var string[] */
    private $alias = [];

    /** @var null|Item */
    private $item = null;

    /** @var null|InvMenu */
    private $brag = null;

    /** @var null|InvMenu */
    private $lastDeathInventory = null;

    /** @var Skin */
    private $originalSkin;

    /** @var null|SurvivalBlockBreakHandler */
    protected $breakHandler = null;

    /** @var array<Vector3, [Block, int]> */
    protected $blockBreakProgress = []; // Remembered Vector to block and break progress data.

    /** @var int */
    protected $lastDeath = 0;

    /**
     * @param Nexus $core
     */
    public function load(Nexus $core): void {
        $this->core = $core;
        $this->originalSkin = $this->skin;
        $this->loadTime = time();
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new LoadPlayerTask($this), 1);
        $this->scoreboard = new Scoreboard($this);
        $this->bossBar = new BossBar($this);
        $this->dataSession = new DataSession($this);
        $this->ceSession = $core->getPlayerManager()->getCESession($this);
        $this->ceSession->reset();
        $nick = $core->getPlayerManager()->getNickname($this);
        if($nick !== null) {
            $this->setDisplayName("~" . $nick);
        }
        $this->zone = $core->getGameManager()->getZoneManager()->getZoneInPosition($this->getPosition());
        $connector = Nexus::getInstance()->getMySQLProvider()->getConnector();
        $this->alias[] = $this->getName();
        $connector->executeSelect("SELECT ipAddress FROM ipAddress WHERE username = ? AND riskLevel = 0", "s", [
            $this->getName()
        ], function(array $rows) use($connector): void {
            foreach($rows as ["ipAddress" => $ipAddress]) {
                $connector->executeSelect("SELECT username FROM ipAddress WHERE ipAddress = ?", "s", [
                    $ipAddress
                ], function(array $rows) use($connector): void {
                    foreach($rows as ["username" => $username]) {
                        $this->alias[] = $username;
                    }
                });
            }
        });
        if($this->getWorld()->getFolderName() === "executive" || $this->getWorld()->getFolderName() === "boss") {
            $this->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
        }
        $this->getInventory()->remove(VanillaBlocks::END_STONE()->asItem());
        $this->alias = array_unique($this->alias);
    }

    /**
     * @return bool
     */
    public function justLoaded(): bool {
        return (time() - $this->loadTime) <= 30;
    }

    /**
     * @return bool
     */
    public function isLoaded(): bool {
        return $this->ceSession !== null and $this->dataSession !== null and $this->dataSession->isLoaded();
    }

    /**
     * @return null|DataSession
     */
    public function getDataSession(): ?DataSession {
        return $this->dataSession;
    }

    /**
     * @return Nexus
     */
    public function getCore(): Nexus {
        return $this->core;
    }

    /**
     * @param bool $disguise
     */
    public function setDisguise(bool $disguise): void {
        $this->disguise = $disguise;
    }

    /**
     * @return bool
     */
    public function isDisguise(): bool {
        return $this->disguise;
    }

    /**
     * @return Rank|null
     */
    public function getDisguiseRank(): ?Rank {
        return $this->disguiseRank;
    }

    /**
     * @param Rank|null $disguiseRank
     */
    public function setDisguiseRank(?Rank $disguiseRank): void {
        $this->disguiseRank = $disguiseRank;
        if($this->getDisguiseRank() !== null) {
            $this->setNameTag($this->getDisguiseRank()->getTagFormatFor($this, [
                "gangRole" => "",
                "gang" => "",
                "level" => $this->dataSession->getPrestigeTag()
            ]));
            return;
        }
//        $this->setNameTag($this->dataSession->getRank()->getTagFormatFor($this, [
//            "gangRole" => $this->dataSession->getGangRoleToString(),
//            "gang" => $this->dataSession->getGang() instanceof Gang ? $this->dataSession->getGang()->getName() . " " : "",
//            "level" => $this->dataSession->getPrestigeTag()
//        ]));
        $this->dataSession->updateNameTag();
    }

    /**
     * @return bool
     */
    public function isBreaking(): bool {
        return $this->breaking;
    }

    /**
     * @param bool $value
     */
    public function setBreaking(bool $value = true): void {
        $this->breaking = $value;
    }

    /**
     * @param Block|null $block
     */
    public function setBlock(?Block $block = null): void {
        $this->block = $block;
    }

    /**
     * @return Block|null
     */
    public function getBlock(): ?Block {
        return $this->block;
    }

    /**
     * @param bool $value
     */
    public function combatTag(bool $value = true): void {
        if($value) {
            $this->combatTag = time();
            return;
        }
        $this->combatTag = 0;
    }

    /**
     * @return bool
     */
    public function isTagged(): bool {
        if(is_null($this->core)) {
            return false;
        }
        return (time() - $this->combatTag) <= 15 && $this->core->getServerManager()->getAnnouncementManager()->getRestarter()->getRestartProgress() > 5;
    }

    /**
     * @return int
     */
    public function getCombatTagTime(): int {
        return $this->combatTag;
    }

    /**
     * @return bool
     */
    public function isFrozen(): bool {
        return $this->frozen;
    }

    /**
     * @param bool $frozen
     */
    public function setFrozen(bool $frozen = true): void {
        $this->frozen = $frozen;
    }

    /**
     * @return bool
     */
    public function isRunningCrateAnimation(): bool {
        return $this->runningCrateAnimation;
    }

    /**
     * @param bool $value
     */
    public function setRunningCrateAnimation(bool $value = true): void {
        $this->runningCrateAnimation = $value;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public function isRequestingTeleport(NexusPlayer $player): bool {
        return isset($this->teleportRequests[$player->getUniqueId()->toString()]) and (time() - $this->teleportRequests[$player->getUniqueId()->toString()]) < 30;
    }

    /**
     * @param NexusPlayer $player
     */
    public function addTeleportRequest(NexusPlayer $player): void {
        $this->teleportRequests[$player->getUniqueId()->toString()] = time();
    }

    /**
     * @param NexusPlayer $player
     */
    public function removeTeleportRequest(NexusPlayer $player): void {
        if(isset($this->teleportRequests[$player->getUniqueId()->toString()])) {
            unset($this->teleportRequests[$player->getUniqueId()->toString()]);
        }
    }

    /**
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public  function isRequestingTrade(NexusPlayer $player): bool {
        return isset($this->tradeRequests[$player->getUniqueId()->toString()]) and (time() - $this->tradeRequests[$player->getUniqueId()->toString()]) < 30;
    }

    /**
     * @param NexusPlayer $player
     */
    public function addTradeRequest(NexusPlayer $player): void {
        $this->tradeRequests[$player->getUniqueId()->toString()] = time();
    }

    /**
     * @param NexusPlayer $player
     */
    public function removeTradeRequest(NexusPlayer $player): void {
        if(isset($this->tradeRequests[$player->getUniqueId()->toString()])) {
            unset($this->tradeRequests[$player->getUniqueId()->toString()]);
        }
    }

    /**
     * @return bool
     */
    public function isTeleporting(): bool {
        return $this->teleporting;
    }

    /**
     * @param bool $value
     */
    public function setTeleporting(bool $value = true): void {
        $this->teleporting = $value;
    }

    /**
     * @return bool
     */
    public function hasVoted(): bool {
        return $this->voted;
    }

    /**
     * @param bool $value
     */
    public function setVoted(bool $value = true): void {
        $this->voted = $value;
    }

    /**
     * @param bool $value
     */
    public function setCheckingForVote(bool $value = true): void {
        $this->voteChecking = $value;
    }

    /**
     * @return bool
     */
    public function isCheckingForVote(): bool {
        return $this->voteChecking;
    }

    /**
     * @param CommandSender $sender
     */
    public function setLastTalked(CommandSender $sender): void {
        $this->lastTalked = $sender;
    }

    /**
     * @return CommandSender|null
     */
    public function getLastTalked(): ?CommandSender {
        if($this->lastTalked === null) {
            return null;
        }
        if(!$this->lastTalked instanceof NexusPlayer) {
            return null;
        }
        return $this->lastTalked->isOnline() ? $this->lastTalked : null;
    }

    /**
     * @return CESession
     */
    public function getCESession(): CESession {
        return $this->ceSession;
    }

    /**
     * @return bool
     */
    public function hasCESession(): bool {
        return $this->ceSession !== null;
    }

    /**
     * @param bool $value
     */
    public function vanish(bool $value = true): void {
        if($value) {
            /** @var NexusPlayer $player */
            foreach($this->getServer()->getOnlinePlayers() as $player) {
                if($player->isLoaded() === false) {
                    continue;
                }
                $rankId = $player->getDataSession()->getRank()->getIdentifier();
                if($rankId >= Rank::TRAINEE and $rankId <= Rank::EXECUTIVE) {
                    continue;
                }
                $player->hidePlayer($this);
            }
            $this->setAllowFlight(true);
            $this->setFlying(true);
            $this->keepMovement = true;
            $this->onGround = false;
//            $pk = new AdventureSettingsPacket();
//            $pk->setFlag(AdventureSettingsPacket::WORLD_IMMUTABLE, true);
//            $pk->setFlag(AdventureSettingsPacket::NO_PVP, true);
//            $pk->setFlag(AdventureSettingsPacket::AUTO_JUMP, $this->autoJump);
//            $pk->setFlag(AdventureSettingsPacket::ALLOW_FLIGHT, $this->allowFlight);
//            $pk->setFlag(AdventureSettingsPacket::NO_CLIP, true);
//            $pk->setFlag(AdventureSettingsPacket::FLYING, $this->flying);
//            $pk->commandPermission = ($this->hasPermission(DefaultPermissions::ROOT_OPERATOR) ? AdventureSettingsPacket::PERMISSION_OPERATOR : AdventureSettingsPacket::PERMISSION_NORMAL);
//            $pk->playerPermission = ($this->hasPermission(DefaultPermissions::ROOT_OPERATOR) ? PlayerPermissions::OPERATOR : PlayerPermissions::MEMBER);
//            $pk->entityUniqueId = $this->getId();
//            $this->getNetworkSession()->sendDataPacket($pk);
        }
        else {
            foreach($this->getServer()->getOnlinePlayers() as $player) {
                if(!$player->canSee($this)) {
                    $player->showPlayer($this);
                }
            }
            $this->checkGroundState(0, 0, 0, 0, 0, 0);
            if($this->isSurvival()){
                $this->setFlying(false);
                $this->setAllowFlight(false);
            }
            $this->sendPosition($this->getPosition(), null, null, MovePlayerPacket::MODE_TELEPORT);
//            $pk = new AdventureSettingsPacket();
//            $pk->setFlag(AdventureSettingsPacket::WORLD_IMMUTABLE, false);
//            $pk->setFlag(AdventureSettingsPacket::NO_PVP, false);
//            $pk->setFlag(AdventureSettingsPacket::AUTO_JUMP, $this->autoJump);
//            $pk->setFlag(AdventureSettingsPacket::ALLOW_FLIGHT, $this->allowFlight);
//            $pk->setFlag(AdventureSettingsPacket::NO_CLIP, false);
//            $pk->setFlag(AdventureSettingsPacket::FLYING, $this->flying);
//            $pk->commandPermission = ($this->hasPermission(DefaultPermissions::ROOT_OPERATOR) ? AdventureSettingsPacket::PERMISSION_OPERATOR : AdventureSettingsPacket::PERMISSION_NORMAL);
//            $pk->playerPermission = ($this->hasPermission(DefaultPermissions::ROOT_OPERATOR) ? PlayerPermissions::OPERATOR : PlayerPermissions::MEMBER);
//            $pk->entityUniqueId = $this->getId();
//            $this->getNetworkSession()->sendDataPacket($pk);
        }
        $this->vanish = $value;
    }

    /**
     * @return bool
     */
    public function hasVanished(): bool {
        return $this->vanish;
    }

    /**
     * @return int
     */
    public function getChatMode(): int {
        return $this->chatMode;
    }

    /**
     * @param int $mode
     */
    public function setChatMode(int $mode): void {
        $this->chatMode = $mode;
    }

    /**
     * @return string
     */
    public function getChatModeToString(): string {
        switch($this->chatMode) {
            case self::PUBLIC:
                return "public";
            case self::GANG:
                return "gang";
            case self::ALLY:
                return "ally";
            case self::STAFF:
                return "staff";
            default:
                return "unknown";
        }
    }

    public function initializeScoreboard(): void {
        $this->scoreboard->spawn(Nexus::SERVER_NAME);
        $this->scoreboard->setScoreLine(1, "");
        $this->scoreboard->setScoreLine(2, $this->dataSession->getRank()->getColoredName());
        $this->scoreboard->setScoreLine(3, TextFormat::WHITE . "   " . $this->getDisplayName());
        $this->scoreboard->setScoreLine(4, TextFormat::GREEN . TextFormat::BOLD . "Balance");
        $this->scoreboard->setScoreLine(5, TextFormat::WHITE . "   $" . number_format($this->dataSession->getBalance(), 2));
        $this->scoreboard->setScoreLine(6, TextFormat::YELLOW . TextFormat::BOLD . "Level");
        $this->scoreboard->setScoreLine(7, "   " . $this->dataSession->getLevelTag() . TextFormat::GRAY . " (" . number_format((int)$this->dataSession->getXP()) . ")");
        $this->scoreboard->setScoreLine(8, TextFormat::GOLD . TextFormat::BOLD . "Progress");
        $nextLevel = (XPUtils::xpToLevel($this->dataSession->getXP()) + 1) <= 100 ? (XPUtils::xpToLevel($this->dataSession->getXP()) + 1) : "prestige";
        $this->scoreboard->setScoreLine(9, TextFormat::GRAY . "   " . Utils::shrinkNumber((int)XPUtils::getNeededXP($this->dataSession->getXP(), $this->dataSession->getPrestige())) . "(" . TextFormat::GREEN . XPUtils::getXPProgress($this->dataSession->getXP(), $this->dataSession->getPrestige(), RPGManager::MODIFIER, 1) . "%%%%" . TextFormat::GRAY . ") to " . TextFormat::WHITE . $nextLevel);
        $tax = $this->guardTax * 100;
        if($tax === 0) {
            if($this->scoreboard->getLine(10) !== null) {
                $this->scoreboard->removeLine(10);
            }
        }
        else {
            $tax = $tax . "%%%%";
            $this->scoreboard->setScoreLine(10, TextFormat::RED . TextFormat::BOLD . "Guard XP Tax $tax");
        }
    }

    /**
     * @return Scoreboard
     */
    public function getScoreboard(): Scoreboard {
        return $this->scoreboard;
    }

    /**
     * @return BossBar
     */
    public function getBossBar(): BossBar {
        return $this->bossBar;
    }

    public function initializeFloatingTexts(): void {
        $default = Nexus::getInstance()->getServer()->getWorldManager()->getDefaultWorld();
        $this->addFloatingText(LevelManager::stringToPosition(LevelManager::getSetup()->getNested("floating.wormhole"), $default), "Wormhole", TextFormat::DARK_AQUA . TextFormat::BOLD . "Wormhole\n \n" . TextFormat::RESET . TextFormat::WHITE . wordwrap("This mysterious space warp will transform your pickaxe points into powerful enchantments and fully energized enchantment books into a greater tier. This distort will also revert your armor pieces to its best condition.", 50));
        $this->addFloatingText(LevelManager::stringToPosition(LevelManager::getSetup()->getNested("floating.guards"), $default), "Guards", TextFormat::RED . TextFormat::BOLD . "Guards\n \n" . TextFormat::RESET . TextFormat::WHITE . wordwrap("Guards oversee the spawn and all mines (except Emerald) to ensure that there is no violence between gangs. In higher tiered mines, guards will not be able to stop all fights. However, the more guards you kill, the harder it will be to kill their replacement. You may see you criminal record by doing /crec, which will reset every time you die.", 50));
        $this->addFloatingText(LevelManager::stringToPosition(LevelManager::getSetup()->getNested("floating.energy"), $default), "Energy", TextFormat::AQUA . TextFormat::BOLD . "Energy\n \n" . TextFormat::RESET . TextFormat::WHITE . wordwrap("Energy is the most valuable resource at this prison. Some of its many uses are purchasing enchantment books, energizing utilities, and making deals with other players. You can obtain energy by doing almost anything! (mining, opening shards, lootboxes, and etc...)", 50));
        $this->addFloatingText(LevelManager::stringToPosition(LevelManager::getSetup()->getNested("floating.shop"), $default), "Shop", TextFormat::YELLOW . TextFormat::BOLD . "Shop\n \n" . TextFormat::RESET . TextFormat::WHITE . wordwrap("Looking for the shop? It is right in this area! You will not be able to access shop while mining or PvP-ing so stock up while you can!", 50));
        $this->addFloatingText(LevelManager::stringToPosition(LevelManager::getSetup()->getNested("floating.tutorial"), $default), "Tutorial", TextFormat::GOLD . TextFormat::BOLD . "New to the server?\n \n" . TextFormat::RESET . TextFormat::WHITE . TextFormat::colorize("Here are some basic commands you should familiarize yourself with.\n \n&e&l/boosts:&r Active boosters\n&e&l/drinklimit:&r Check daily XP drinking limit\n&e&l/extract:&r Extract energy from utilities and books\n&e&l/levelcap:&r Check the maximum level limit\n&e&l/fund:&r View global funding progress\n&e&l/mines:&r Check accessible mines\n&e&l/rested:&r Check remaining time for Rested XP\n&e&l/xpextract:&r Extract your experience into a bottle\n&e&l/bb:&r Manage your bank block"));
        if(date("l") !== "Friday") {
            $this->addFloatingText(LevelManager::stringToPosition(LevelManager::getSetup()->getNested("floating.mystery"), $default), "Mystery", TextFormat::AQUA . "This is where you will find the\n" . TextFormat::BOLD . TextFormat::GOLD . "Mystery Man! " . TextFormat::RESET . TextFormat::AQUA . "He appears every Friday\n" . TextFormat::AQUA . "The " . TextFormat::BOLD . TextFormat::GOLD . "Mystery Man " . TextFormat::RESET . TextFormat::AQUA . "sells rare items in exchange\n" . TextFormat::AQUA . "for "  . TextFormat::BOLD . TextFormat::GOLD . "Tokens! " . TextFormat::RESET . TextFormat::AQUA . "You earn tokens by completing /quest!");
        }
        foreach (SpawnRushOreTask::$fancyFloaties as $id => $floating) {
            $this->floatingTexts[$id] = $floating;
            $floating->sendChangesTo($this);
        }
//        if(count(RushOreBlock::$enabledFloatings) > 0) {
//            foreach (RushOreBlock::$enabledFloatings as $identifier => [$info, $pos]) {
//                $this->addFloatingText($pos, $identifier, $info);
//            }
//        }
//        if(count(ExecutiveMineHeartbeatTask::$floatingTexts) > 0) {
//            foreach (ExecutiveMineHeartbeatTask::$floatingTexts as $identifier => [$info, $pos]) {
//                $this->addFloatingText($pos, $identifier, $info);
//            }
//        }
    }

    /**
     * @param Position $position
     * @param string $identifier
     * @param string $message
     *
     * @throws UtilsException
     */
    public function addFloatingText(Position $position, string $identifier, string $message): void {
        if($position->getWorld() === null) {
            throw new UtilsException("Attempt to add a floating text particle with an invalid level.");
        }
        $floatingText = new FloatingTextParticle($position, $identifier, $message);
        $this->floatingTexts[$identifier] = $floatingText;
        $floatingText->sendChangesTo($this);
    }

    /**
     * @param string $identifier
     *
     * @throws UtilsException
     */
    public function removeFloatingText(string $identifier): void {
        $floatingText = $this->getFloatingText($identifier);
        if($floatingText === null) {
            var_dump("Failed to despawn floating text: $identifier");
            return;
            //throw new UtilsException("Failed to despawn floating text: $identifier");
        }
        $floatingText->despawn($this);
        unset($this->floatingTexts[$identifier]);
    }

    /**
     * @param string $identifier
     *
     * @return FloatingTextParticle|null
     */
    public function getFloatingText(string $identifier): ?FloatingTextParticle {
        return $this->floatingTexts[$identifier] ?? null;
    }

    /**
     * @return int
     *
     * @throws LevelException
     */
    public function getArmorPoints(): int {
        $total = 0;
        if($this->zone !== null) {
            foreach($this->armorInventory->getContents() as $item) {
                if(ItemManager::canUseTool($this, $item)) {
                    if($item instanceof Armor) {
                        if($item->getTierId() > $this->zone->getTierId()) {
                            $total += ZoneManager::getArmorPoints($item->getArmorSlot(), $this->zone->getTierId());
                            continue;
                        }
                        $total += ZoneManager::getArmorPoints($item->getArmorSlot(), $item->getTierId());
                    }
                }
            }
        }
        else {
            foreach($this->armorInventory->getContents() as $item) {
                if(ItemManager::canUseTool($this, $item)) {
                    if($item instanceof Armor) {
                        $total += ZoneManager::getArmorPoints($item->getArmorSlot(), $item->getTierId());
                    }
                }
            }
        }
        return $total;
    }

    /**
     * @param Item $item
     * @return void
     */
    public function removeItemExact(Item $item) : void{

        foreach($this->getInventory()->getContents() as $index => $i){
            if($item->equals($i, false, true)){
                $this->getInventory()->clear($index);
            }
        }
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function attackEntity(Entity $entity): bool {
        if(!$entity->isAlive()) {
            return false;
        }
        if($entity instanceof ItemEntity or $entity instanceof Arrow) {
            $this->logger->debug("Attempted to attack non-attackable entity " . get_class($entity));
            return false;
        }
        $heldItem = $this->inventory->getItemInHand();
        $oldItem = clone $heldItem;
        $zone = $this->getZone();
        $attackPoints = $heldItem->getAttackPoints();
        if($zone !== null) {
            $attackPoints = ZoneListener::TIER_TO_ATTACK_POINTS[$zone->getTierId()];
            if($heldItem instanceof Axe or $heldItem instanceof Sword) {
                if($zone->getTierId() > ZoneListener::ITEM_TO_ARMOR_TIER[$heldItem->getTier()->getHarvestLevel()]) {
                    $attackPoints = $heldItem->getAttackPoints();
                }
            }
        }
        $ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $attackPoints);
        if($this->isSpectator() or !$this->canInteract($entity->getLocation(), 8) or ($entity instanceof Player and !$this->server->getConfigGroup()->getConfigBool("pvp"))) {
            $ev->cancel();
        }
        $meleeEnchantmentDamage = 0;
        /** @var EnchantmentInstance[] $meleeEnchantments */
        $meleeEnchantments = [];
        foreach($heldItem->getEnchantments() as $enchantment) {
            $type = $enchantment->getType();
            if($type instanceof MeleeWeaponEnchantment and $type->isApplicableTo($entity)) {
                $meleeEnchantmentDamage += $type->getDamageBonus($enchantment->getLevel());
                $meleeEnchantments[] = $enchantment;
            }
        }
        $ev->setModifier($meleeEnchantmentDamage, EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);
        if(!$this->isSprinting() and !$this->isFlying() and $this->fallDistance > 0 and !$this->effectManager->has(VanillaEffects::BLINDNESS()) and !$this->isUnderwater()) {
            $ev->setModifier($ev->getFinalDamage() / 2, EntityDamageEvent::MODIFIER_CRITICAL);
        }
        $entity->attack($ev);
        $soundPos = $entity->getPosition()->add(0, $entity->size->getHeight() / 2, 0);
        if($ev->isCancelled()) {
            $this->getWorld()->addSound($soundPos, new EntityAttackNoDamageSound());
            return false;
        }
        $this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());
        $this->getWorld()->addSound($soundPos, new EntityAttackSound());
        if($ev->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0 and $entity instanceof Living) {
            $entity->broadcastAnimation(new CriticalHitAnimation($entity));
        }
        foreach($meleeEnchantments as $enchantment) {
            $type = $enchantment->getType();
            assert($type instanceof MeleeWeaponEnchantment);
            $type->onPostAttack($this, $entity, $enchantment->getLevel());
        }
        if($this->isAlive()) {
            //reactive damage like thorns might cause us to be killed by attacking another mob, which
            //would mean we'd already have dropped the inventory by the time we reached here
            if($heldItem->onAttackEntity($entity) and $this->hasFiniteResources() and $oldItem->equalsExact($this->inventory->getItemInHand())) { //always fire the hook, even if we are survival
                if($heldItem instanceof Durable && $heldItem->isBroken()) {
                    $this->broadcastSound(new ItemBreakSound());
                }
                $this->inventory->setItemInHand($heldItem);
            }
            $this->hungerManager->exhaust(0.3, PlayerExhaustEvent::CAUSE_ATTACK);
        }
        return true;
    }

    /**
     * @param Item $item
     * @param bool $compress
     */
    public function addItem(Item $item, bool $compress = false): void {
        $inventory = $this->getInventory();
        if($inventory->firstEmpty() === -1) {
            $this->getWorld()->dropItem($this->getPosition(), $item);
            return;
        }
        $items = [
            ItemIds::COAL,
            ItemIds::IRON_INGOT,
            ItemIds::DYE,
            ItemIds::REDSTONE,
            ItemIds::GOLD_INGOT,
            ItemIds::DIAMOND,
            ItemIds::EMERALD,
            ItemIds::PRISMARINE
        ];
        $sellables = Nexus::getInstance()->getGameManager()->getEconomyManager()->getSellables();
        if($compress) {
            if(Energy::isInstanceOf($item)) {
                $orb = Energy::fromItem($item);
                foreach($inventory->getContents() as $content) {
                    if(Energy::isInstanceOf($content)) {
                        $orb2 = Energy::fromItem($content);
                        $inventory->removeItem($content);
                        $energy = $orb->getEnergy() + $orb2->getEnergy();
                        $inventory->addItem((new Energy($energy, $this->getName()))->toItem());
                        return;
                    }
                }
            }
            if(MoneyNote::isInstanceOf($item)) {
                $note = MoneyNote::fromItem($item);
                foreach($inventory->getContents() as $content) {
                    if(MoneyNote::isInstanceOf($content)) {
                        $note2 = MoneyNote::fromItem($content);
                        $inventory->removeItem($content);
                        $money = $note->getBalance() + $note2->getBalance();
                        $inventory->addItem((new MoneyNote($money, $this->getName()))->toItem());
                        return;
                    }
                }
            }
            if(XPBottle::isInstanceOf($item)) {
                $bottle = XPBottle::fromItem($item);
                foreach($inventory->getContents() as $content) {
                    if(XPBottle::isInstanceOf($content)) {
                        $bottle2 = XPBottle::fromItem($content);
                        if($bottle->getRarity() === $bottle2->getRarity()) {
                            $inventory->removeItem($content);
                            $amount = $bottle->getAmount() + $bottle2->getAmount();
                            $inventory->addItem((new XPBottle($amount, $bottle->getRarity()))->toItem());
                            return;
                        }
                    }
                }
            }
            elseif($item->getBlock() instanceof Ore || in_array($item->getId(), $items)) {
                if(isset($sellables[$item->getId()])) {
                    $profit = $sellables[$item->getId()]->getSellPrice() * $item->getCount();
                    foreach($inventory->getContents() as $content) {
                        if(MoneyNote::isInstanceOf($content)) {
                            $note = MoneyNote::fromItem($content);
                            $inventory->removeItem($content);
                            $balance = $note->getBalance() + $profit;
                            $inventory->addItem((new MoneyNote($balance, $this->getName()))->toItem());
                            return;
                        }
                    }
                    $inventory->addItem((new MoneyNote($profit))->toItem());
                }
                return;
            }
            $inventory->addItem($item);
        }
        else {
            if($item->getBlock() instanceof Ore or in_array($item->getId(), $items)) {
                foreach($inventory->getContents() as $slot => $content) {
                    if(Satchel::isInstanceOf($content)) {
                        $satchel = Satchel::fromItem($content);
                        $type = $satchel->getType();
                        if($type->equals($item)) {
                            $energy = $satchel->getEnergy();
                            $amount = $satchel->getAmount();
                            $level = XPUtils::xpToLevel($energy, RPGManager::SATCHEL_MODIFIER);
                            $max = ($level + 1) * 2304;
                            if($amount < $max) {
                                $energy = 0;
                                $count = $item->getCount();
                                $doubleDrop = $content->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::DOUBLE_DROP));
                                if($doubleDrop > 0) {
                                    if($doubleDrop >= mt_rand(1, 30)) {
                                        $count *= 2;
                                    }
                                }
                                $energyMirror = $content->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::ENERGY_MIRROR));
                                if($energyMirror > 0) {
                                    $energy = mt_rand(100, 800) * $energyMirror;
                                }
                                $snatch = $content->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::SNATCH));
                                if($snatch > 0) {
                                    if($snatch >= mt_rand(1, 20)) {
                                        $bb = $this->getBoundingBox()->expandedCopy(15, 15, 15);
                                        $level = $this->getWorld();
                                        if($level !== null) {
                                            foreach($level->getNearbyEntities($bb) as $e) {
                                                if($e->getId() === $this->getId()) {
                                                    continue;
                                                }
                                                $count += $snatch * mt_rand(1, 5);
                                            }
                                        }
                                    }
                                }
                                $autoSell = $content->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::AUTO_SELL));
                                if($autoSell > 0) {
                                    if($autoSell >= mt_rand(1, 15)) {
                                        $profit = $sellables[$item->getId()]->getSellPrice();
                                        $this->getDataSession()->addToBalance($profit * $item->getCount(), false);
                                        $count = 0;
                                    }
                                }
                                $satchel->addEnergy($energy);
                                $satchel->setAmount($satchel->getAmount() + $count);
                                $inventory->setItem($slot, $satchel->toItem());
                                return;
                            }
                        }
                    }
                }
            }
            $inventory->addItem($item);
        }
    }

    public function sendDelayedWindow(InvMenu $menu): void {
        $this->getCore()->getScheduler()->scheduleDelayedTask(new class($menu, $this) extends Task {

            /** @var InvMenu */
            private $menu;

            /** @var NexusPlayer */
            private $player;

            /**
             *  constructor.
             *
             * @param InvMenu $menu
             * @param NexusPlayer $player
             */
            public function __construct(InvMenu $menu, NexusPlayer $player) {
                $this->menu = $menu;
                $this->player = $player;
            }

            /**
             * @param int $currentTick
             */
            public function onRun(): void {
                if($this->player->isOnline() and (!$this->player->isClosed())) {
                    $this->menu->send($this->player);
                }
            }
        }, 10);
    }

    /**
     * @param Form $form
     */
    public function sendDelayedForm(Form $form): void {
        $this->getCore()->getScheduler()->scheduleDelayedTask(new class($form, $this) extends Task {

            /** @var Form */
            private $form;

            /** @var NexusPlayer */
            private $player;

            /**
             *  constructor.
             *
             * @param Form $form
             * @param NexusPlayer $player
             */
            public function __construct(Form $form, NexusPlayer $player) {
                $this->form = $form;
                $this->player = $player;
            }

            /**
             * @param int $currentTick
             */
            public function onRun(): void {
                if($this->player->isOnline() and (!$this->player->isClosed())) {
                    $this->player->sendForm($this->form);
                }
            }
        }, 10);
    }

    /**
     * @param float $pitch
     * @param int $volume
     */
    public function playDingSound(float $pitch = 1.0, int $volume = 400): void {
        $pk = new PlaySoundPacket();
        $pk->soundName = "random.levelup";
        $pk->x = $this->getPosition()->x;
        $pk->y = $this->getPosition()->y;
        $pk->z = $this->getPosition()->z;
        $pk->volume = $volume;
        $pk->pitch = $pitch;
        $this->getNetworkSession()->sendDataPacket($pk);
    }

    public function playConsecutiveDingSound(): void {
        $this->getCore()->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {

            /** @var NexusPlayer */
            private $player;

            /** @var int */
            private $runs = 0;

            /**
             *  constructor.
             *
             * @param Form $form
             * @param NexusPlayer $player
             */
            public function __construct(NexusPlayer $player) {
                $this->player = $player;
            }

            /**
             * @param int $currentTick
             */
            public function onRun(): void {
                if(++$this->runs > 3) {
                    $this->cancel();
                    return;
                }
                if($this->player->isOnline() and (!$this->player->isClosed())) {
                    $this->player->playDingSound();
                }
            }
        }, 4);
    }

    /**
     * @param float $pitch
     * @param int $volume
     */
    public function playOrbSound(float $pitch = 1.0, int $volume = 400): void {
        $pk = new PlaySoundPacket();
        $pk->soundName = "random.orb";
        $pk->x = $this->getPosition()->x;
        $pk->y = $this->getPosition()->y;
        $pk->z = $this->getPosition()->z;
        $pk->volume = $volume;
        $pk->pitch = $pitch;
        $this->getNetworkSession()->sendDataPacket($pk);
    }

    public function playNoteSound(int $extraData = -1): void {
        $pk = new LevelSoundEventPacket();
        $pk->sound = LevelSoundEvent::NOTE;
        $pk->position = $this->getPosition();
        $pk->extraData = $extraData;
        $this->getNetworkSession()->sendDataPacket($pk);
    }

    /**
     * @param int $extraData
     */
    public function playLaunchSound(int $extraData = -1): void {
        $pk = new LevelSoundEventPacket();
        $pk->position = $this->getPosition();
        $pk->sound = LevelSoundEvent::LAUNCH;
        $pk->extraData = $extraData;
        $this->getNetworkSession()->sendDataPacket($pk);
    }

    /**
     * @param int $extraData
     */
    public function playBlastSound(int $extraData = -1): void {
        $pk = new LevelSoundEventPacket();
        $pk->position = $this->getPosition();
        $pk->sound = LevelSoundEvent::LARGE_BLAST;
        $pk->extraData = $extraData;
        $this->getNetworkSession()->sendDataPacket($pk);
    }

    /**
     * @param int $extraData
     */
    public function playTwinkleSound(int $extraData = -1): void {
        $pk = new LevelSoundEventPacket();
        $pk->position = $this->getPosition();
        $pk->sound = LevelSoundEvent::TWINKLE;
        $pk->extraData = $extraData;
        $this->getNetworkSession()->sendDataPacket($pk);
    }

    public function playErrorSound(): void {
        $this->playNoteSound(0);
    }

    /**
     * @param Vector3 $pos
     * @param float|null $yaw
     * @param float|null $pitch
     *
     * @return bool
     */
    public function teleport(Vector3 $pos, float $yaw = null, float $pitch = null): bool {
        $return = parent::teleport($pos, $yaw, $pitch);
        if($pos instanceof Position) {
            $level = $pos->getWorld();
            if($level !== null) {
                foreach($this->getFloatingTexts() as $floatingText) {
                    if($level->getFolderName() === $floatingText->getLevel()->getFolderName()) {
                        $floatingText->spawn($this);
                        continue;
                    }
                    if($level->getFolderName() !== $floatingText->getLevel()->getFolderName()) {
                        $floatingText->despawn($this);
                        continue;
                    }
                }
            }
        }
        $this->breakHandler = null;
        return $return;
    }

    /**
     * @param int $currentTick
     *
     * @return bool
     */
    public function onUpdate(int $currentTick): bool {
        $tickDiff = $currentTick - $this->lastUpdate;
        if($tickDiff <= 0) {
            return true;
        }
        $this->messageCounter = 2;
        $this->lastUpdate = $currentTick;
        if(!$this->isAlive() and $this->spawned) {
            $this->onDeathUpdate($tickDiff);
            return true;
        }
        $this->timings->startTiming();
        if($this->spawned) {
            $this->processMostRecentMovements();
            $this->motion = new Vector3(0, 0, 0); //TODO: HACK! (Fixes player knockback being messed up)
            if($this->onGround) {
                $this->inAirTicks = 0;
            }
            else {
                $this->inAirTicks += $tickDiff;
            }
            Timings::$entityBaseTick->startTiming();
            $this->entityBaseTick($tickDiff);
            Timings::$entityBaseTick->stopTiming();
            if(!$this->isSpectator() and $this->isAlive()) {
                Timings::$playerCheckNearEntities->startTiming();
                $this->checkNearEntities();
                Timings::$playerCheckNearEntities->stopTiming();
            }
            if($this->breakHandler !== null and !$this->breakHandler->update()) {
                $this->breakHandler = null;
            }
        }
        $this->timings->stopTiming();
        return true;
    }

    /**
     * @param Vector3 $pos
     * @param int $face
     */
    public function continueBreakBlock(Vector3 $pos, int $face): void {
        if($this->breakHandler !== null and $this->breakHandler->getBlockPos()->distanceSquared($pos) < 0.0001) {
            //TODO: check the targeted block matches the one we're told to target
            $this->breakHandler->setTargetedFace($face);
        }
    }

    /**
     * @param string $reason
     * @param Translatable|string|null $quitMessage
     */
    public function onPostDisconnect(string $reason, Translatable|string|null $quitMessage): void {
        parent::onPostDisconnect($reason, $quitMessage);
        $this->breakHandler = null;
        $this->blockBreakProgress = [];
    }

    /**
     * @param Vector3 $pos
     */
    public function stopBreakBlock(Vector3 $pos): void {
        if($this->breakHandler !== null and $this->breakHandler->getBlockPos()->distanceSquared($pos) < 0.0001) {
            $this->breakHandler = null;
        }
    }

    /**
     * @param Vector3 $pos
     * @param Block $block
     * @param float $progress
     * @return void
     */
    public function setBlockBreakProgress(Vector3 $pos, Block $block, float $progress) : void {
        $this->blockBreakProgress[(string)$pos] = [$block, $progress];
        if(count($this->blockBreakProgress) > 50) array_shift($this->blockBreakProgress);
    }

    /**
     * @param Vector3 $pos
     * @return void
     */
    public function removeBlockBreakProgress(Vector3 $pos) : void {
        unset($this->blockBreakProgress[(string)$pos]);
    }

    public function clearBlockBreakProgress() : void {
        $this->blockBreakProgress = [];
    }

    /**
     * @param Vector3 $pos
     * @return int|array Block and break progress if remembered, null otherwise
     */
    public function getBlockBreakProgress(Vector3 $pos) : ?array {
        return $this->blockBreakProgress[(string)$pos] ?? null;
    }

    private $blockBreakFactor = null;

    public function setBlockBreakFactor(?float $factor = null) : void {
        $this->blockBreakFactor = $factor;
    }

    public function getBlockBreakFactor() : ?float {
        return $this->blockBreakFactor;
    }


    /**
     * @return FloatingTextParticle[]
     */
    public function getFloatingTexts(): array {
        return $this->floatingTexts;
    }

    /**
     * @param string $identifier
     * @param array $parameters
     */
    public function sendTranslatedMessage(string $identifier, array $parameters = []): void {
        try {
            $this->sendMessage(Translation::getMessage($identifier, $parameters));
        }
        catch(TranslationException $exception) {
            $this->sendMessage(TextFormat::RED . $exception->getMessage());
        }
    }

    /**
     * @param Permission|string $name
     *
     * @return bool
     */
    public function hasPermission($name): bool {
        $noPermission = "-" . $name;
        if($this->isLoaded()) {
            if(in_array($name, $this->dataSession->getPermissions())) {
                if(in_array($noPermission, $this->dataSession->getPermissions())) {
                    return false;
                }
                return true;
            }
            $rank = $this->dataSession->getRank();
            if($rank !== null) {
                if(in_array($name, $rank->getPermissions())) {
                    return true;
                }
            }
            if(in_array($name, $this->dataSession->getPermanentPermissions())) {
                if(in_array($noPermission, $this->dataSession->getPermanentPermissions())) {
                    return false;
                }
                return true;
            }
        }
        return parent::hasPermission($name);
    }

    /**
     * @param string $message
     * @param int $time
     */
    public function sendAlert(string $message, int $time = 1) {
        if(isset($this->alerts[$message])) {
            if((time() - $this->alerts[$message]) < $time) {
                return;
            }
        }
        $this->alerts[$message] = time();
        $this->sendMessage($message);
    }

    /**
     * @param string $identifier
     * @param array $parameters
     * @param int $time
     */
    public function sendTranslatedAlert(string $identifier, array $parameters = [], int $time = 1) {
        try {
            $message = Translation::getMessage($identifier, $parameters);
        }
        catch(TranslationException $exception) {
            $message = TextFormat::RED . $exception->getMessage();
        }
        if(isset($this->alerts[$message])) {
            if((time() - $this->alerts[$message]) < $time) {
                return;
            }
        }
        $this->alerts[$message] = time();
        $this->sendMessage($message);
    }

    /**
     * @param string $message
     * @param int $time
     */
    public function sendPopupTo(string $message, int $time = 1) {
        if(isset($this->alerts[$message])) {
            if((time() - $this->alerts[$message]) < $time) {
                return;
            }
        }
        $this->alerts[$message] = time();
        parent::sendPopup($message);
    }

    /**
     * @param string $message
     * @param string $subTitle
     * @param int $time
     */
    public function sendTitleTo(string $message, string $subTitle = "", int $time = 1) {
        if(isset($this->alerts[$message])) {
            if((time() - $this->alerts[$message]) < $time) {
                return;
            }
        }
        $this->alerts[$message] = time();
        $this->sendTitle($message, $subTitle);
    }

    /**
     * @param Vector3 $pos
     * @param int $face
     *
     * @return bool
     */
    public function attackBlock(Vector3 $pos, int $face) : bool {
        if($pos->distanceSquared($this->location) > 10000) {
            return false; //TODO: maybe this should throw an exception instead?
        }
        $target = $this->getWorld()->getBlock($pos);
        $ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $target, null, $face, PlayerInteractEvent::LEFT_CLICK_BLOCK);
        if($this->isSpectator()) {
            $ev->cancel();
        }
        $ev->call();
        if($ev->isCancelled()) {
            return false;
        }
        $this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());
        if($target->onAttack($this->inventory->getItemInHand(), $face, $this)) {
            return true;
        }
        $block = $target->getSide($face);
        if($block->getId() === BlockLegacyIds::FIRE) {
            $this->getWorld()->setBlock($block->getPosition(), VanillaBlocks::AIR());
            $this->getWorld()->addSound($block->getPosition()->add(0.5, 0.5, 0.5), new FireExtinguishSound());
            return true;
        }
        if(!$this->isCreative()) {
            $this->breakHandler = SurvivalBlockBreakHandler::createIfNecessary($this, $pos, $target, $face, 16);
        }
        return true;
    }

    /**
     * @param float $damage
     */
    public function damageArmor(float $damage) : void{
        $durabilityRemoved = (int)max(floor($damage / 4), 1);
        $armor = $this->armorInventory->getContents(true);
        foreach($armor as $item) {
            if($item instanceof \pocketmine\item\Armor and (!$item->isBroken())) {
                $level = $item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::CHIVALRY));
                if($level > 0) {
                    $modifier = $this->getMaxHealth() / $this->getHealth();
                    $modifier *= (0.025 * $level);
                    $durabilityRemoved = max(1, $durabilityRemoved - $modifier);
                }
                $this->damageItem($item, (int)ceil($durabilityRemoved));
            }
        }
        $this->armorInventory->setContents($armor);
    }

    /**
     * @param Durable $item
     * @param int $durabilityRemoved
     */
    private function damageItem(Durable $item, int $durabilityRemoved) : void{
        $item->applyDamage($durabilityRemoved);
        if($item->isBroken()){
            $this->broadcastSound(new ItemBreakSound());
        }
    }

    /**
     * @param Zone|null $zone
     */
    public function setZone(?Zone $zone): void {
        $this->zone = $zone;
    }

    /**
     * @return Zone|null
     */
    public function getZone(): ?Zone {
        return $this->zone;
    }

    /**
     * @return int
     */
    public function getGuardTax(): int {
        return $this->guardTax;
    }

    /**
     * @param int $guardTax
     */
    public function setGuardTax(int $guardTax): void {
        $this->guardTax = $guardTax;
        if($this->scoreboard->isSpawned()) {
            if(Nexus::getInstance()->getServerManager()->getAreaManager()->canDamage($this->getPosition())) {
                $tax = $this->guardTax;
            }
            else {
                $tax = 0;
            }
            if($tax === 0) {
                if($this->scoreboard->getLine(10) !== null) {
                    $this->scoreboard->removeLine(10);
                }
            }
            else {
                $tax = $tax . "%%%%";
                $this->scoreboard->setScoreLine(10, TextFormat::RED . TextFormat::BOLD . "Guard XP Tax $tax");
            }
        }
    }

    /**
     * @param int $price
     *
     * @return bool
     */
    public function canPayEnergy(int $price): bool {
        foreach($this->getInventory()->getContents() as $slot => $content) {
            if(!Energy::isInstanceOf($content)) {
                continue;
            }
            $orb = Energy::fromItem($content);
            $energy = $orb->getEnergy();
            if($energy < $price) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * @param int $price
     *
     * @return bool
     */
    public function payEnergy(int $price): bool {
        $paid = false;
        foreach($this->getInventory()->getContents() as $slot => $content) {
            if(!Energy::isInstanceOf($content)) {
                continue;
            }
            $orb = Energy::fromItem($content);
            $energy = $orb->getEnergy();
            if($energy < $price) {
                continue;
            }
            $paid = true;
            $leftOver = $energy - $price;
            if($leftOver > 0) {
                $orb->setEnergy($leftOver);
                $this->getInventory()->setItem($slot, $orb->toItem());
            }
            else {
                $this->getInventory()->setItem($slot, ItemFactory::getInstance()->get(ItemIds::AIR));
            }
            break;
        }
        return $paid;
    }

    protected function onDeath() : void {
        if(time() - $this->lastDeath <= 3) {
            return;
        }
        $this->lastDeath = time();
        $this->removeCurrentWindow();
        $ev = new PlayerDeathEvent($this, $this->getDrops(), $this->getXpDropAmount(), null);
        $ev->call();
        $cause = $this->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if($damager instanceof BaseGuard) {
                $damager->setTarget(null);
                if($ev->getDeathMessage() != "") {
                    $this->server->broadcastMessage($ev->getDeathMessage());
                }
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new FakeDropsTask($this, $ev->getDrops()), 60);
                if($this->inventory !== null) {
                    $this->inventory->setHeldItemIndex(0);
                    $this->inventory->clearAll();
                }
                if($this->armorInventory !== null) {
                    $this->armorInventory->clearAll();
                }
                if($this->offHandInventory !== null){
                    $this->offHandInventory->clearAll();
                }
                if($ev->getDeathMessage() != "") {
                    $this->server->broadcastMessage($ev->getDeathMessage());
                }
                $this->startDeathAnimation();
                $this->networkSession->onServerDeath($ev->getDeathMessage());
                return;
            }
        }
        if(!$ev->getKeepInventory()) {
            foreach($ev->getDrops() as $item) {
                $this->getWorld()->dropItem($this->location, $item);
            }
            if($this->inventory !== null) {
                $this->inventory->setHeldItemIndex(0);
                $this->inventory->clearAll();
            }
            if($this->armorInventory !== null) {
                $this->armorInventory->clearAll();
            }
            if($this->offHandInventory !== null){
                $this->offHandInventory->clearAll();
            }
        }
        if($ev->getDeathMessage() != "") {
            $this->server->broadcastMessage($ev->getDeathMessage());
        }
        $this->startDeathAnimation();
        $this->networkSession->onServerDeath($ev->getDeathMessage());
    }

    public function setLastJet(): void {
        $this->lastJet = time();
    }

    /**
     * @return int
     */
    public function getLastJet(): int {
        return $this->lastJet;
    }

    /**
     * @return bool
     */
    public function isUsingJet(): bool {
        return $this->usingJet;
    }

    /**
     * @param bool $usingJet
     */
    public function setUsingJet(bool $usingJet = true): void {
        if($usingJet) {
            $this->playLaunchSound();
        }
        else {
            $this->playBlastSound();
        }
        $this->usingJet = $usingJet;
    }

    /**
     * @param string $message
     */
    public function addCEPopup(string $message): void {
        $this->cePopups[$message] = time();
        $messages = [];
        foreach($this->cePopups as $message => $time) {
            if((time() - $time) <= 2) {
                if(count($messages) > 4) {
                    array_shift($messages);
                }
                $messages[] = $message;
            }
        }
        $this->sendPopup(implode("\n", $messages) . "\n\n");
    }

    /**
     * @param string $reason
     */
    public function kickDelay(string $reason = ""): void {
        if($this->isOnline() and (!$this->isClosed())) {
            $this->kick($reason);
        }
//        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($reason, $this) extends Task {
//
//            /** @var string */
//            private $reason;
//
//            /** @var NexusPlayer */
//            private $player;
//
//            /**
//             *  constructor.
//             *
//             * @param string $reason
//             * @param NexusPlayer $player
//             */
//            public function __construct(string $reason, NexusPlayer $player) {
//                $this->reason = $reason;
//                $this->player = $player;
//            }
//
//            public function onRun(): void {
//                if($this->player->isOnline() and (!$this->player->isClosed())) {
//                    $this->player->kick($this->reason);
//                }
//            }
//        }, 5);
    }

    /**
     * @return bool
     */
    public function isTakeFallDamage(): bool {
        return $this->takeFallDamage;
    }

    /**
     * @param bool $takeFallDamage
     */
    public function setTakeFallDamage(bool $takeFallDamage): void {
        $this->takeFallDamage = $takeFallDamage;
    }

    /**
     * @param int $time
     * @param string $type
     *
     * @return int
     */
    public function getCooldownLeft(int $time, string $type): int {
        if(!isset($this->cooldowns[$type])) {
            return 0;
        }
        return $time - (time() - $this->cooldowns[$type]);
    }

    /**
     * @param string $type
     */
    public function setCooldown(string $type): void {
        $this->cooldowns[$type] = time();
    }

    /**
     * @return string[]
     */
    public function getAlias(): array {
        return $this->alias;
    }

    /**
     * @param Item|null $item
     */
    public function setItem(?Item $item): void {
        $this->item = $item;
    }

    /**
     * @return Item|null
     */
    public function getItem(): ?Item {
        return $this->item;
    }

    /**
     * @param InvMenu|null $brag
     */
    public function setBrag(?InvMenu $brag): void {
        $this->brag = $brag;
    }

    /**
     * @return InvMenu|null
     */
    public function getBrag(): ?InvMenu {
        return $this->brag;
    }

    /**
     * @param GrapplingHook|null $grapplingHook
     */
    public function setGrapplingHook(?GrapplingHook $grapplingHook): void {
        $this->grapplingHook = $grapplingHook;
    }

    /**
     * @return GrapplingHook|null
     */
    public function getGrapplingHook(): ?GrapplingHook {
        return $this->grapplingHook;
    }

    /**
     * @return Skin
     */
    public function getOriginalSkin(): Skin {
        return $this->originalSkin;
    }
}