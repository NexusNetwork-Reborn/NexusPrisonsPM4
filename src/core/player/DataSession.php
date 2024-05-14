<?php

namespace core\player;

use core\command\forms\ItemInformationForm;
use core\command\forms\ShowcaseActionForm;
use core\command\forms\ShowcaseConfirmationForm;
use core\command\inventory\WarpMenuInventory;
use core\display\animation\AnimationException;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\mask\Mask;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\custom\XPBottle;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Pickaxe;
use core\game\kit\Kit;
use core\game\kit\GodKit;
use core\NexusListener;
use core\player\gang\Gang;
use core\Nexus;
use core\player\rank\Rank;
use core\player\rank\RankManager;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use core\translation\TranslationException;
use core\player\vault\Vault;
use libs\utils\ArrayUtils;
use libs\utils\Utils;
use libs\utils\UtilsException;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\block\BlockToolType;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class DataSession {

    /** @var NexusPlayer */
    private $owner;

    /** @var Nexus */
    private $core;

    /** @var int */
    private $successCount = 0;

    /** @var int */
    private $loadTime = 0;

    /** @var null|Gang */
    private $gang = null;

    /** @var null|int */
    private $gangRole = null;

    /** @var null|Rank */
    private $rank = null;

    /** @var string[] */
    private $permissions = [];

    /** @var string[] */
    private $permanentPermissions = [];

    /** @var int */
    private $votePoints = 0;

    /** @var string[] */
    private $tags = [];

    /** @var Position[] */
    private $homes = [];

    /** @var string */
    private $currentTag = "";

    /** @var int */
    private $additionalVaults = 0;

    /** @var int */
    private $additionalHomes = 0;

    /** @var int */
    private $additionalShowcases = 1;

    /** @var InvMenu */
    private $showcase;

    /** @var InvMenu */
    private $inbox;

    /** @var int */
    private $xp = 0;

    /** @var int */
    private $prestige = 0;

    /** @var float */
    private $balance = 0;

    /** @var int */
    private $blocks = 0;

    /** @var int */
    private $bankBlock = 0;

    /** @var int */
    private $kills = 0;

    /** @var int[] */
    private $kitCooldowns = [];

    /** @var int[] */
    private $kitTiers = [];

    /** @var float */
    private $xpModifier = 1.0;

    /** @var int */
    private $xpTime = 0;

    /** @var int */
    private $xpDuration = 0;

    /** @var float */
    private $energyModifier = 1.0;

    /** @var int */
    private $energyTime = 0;

    /** @var int */
    private $energyDuration = 0;

    /** @var int */
    private $executiveTime = 0;

    /** @var int */
    private $executiveDuration = 0;

    /** @var int */
    private $maxLevelObtained = 0;

    /** @var int */
    private $drinkXPTime = 0;

    /** @var int */
    private $xpDrank = 0;

    /** @var int */
    private $maxDrinkableXP = 0;

    /** @var int */
    private $restedXP = 14400;

    /** @var int */
    private $onlineTime = 0;

    /** @var int */
    private $guardsKilled = 0;

    /** @var int[] */
    private $quests = [];

    /** @var int */
    private $lastQuestReroll = 0;

    /** @var int */
    private $lotteryBought = 0;

    /** @var int */
    private $lotteryWon = 0;

    /** @var int */
    private $lotteryTotalWon = 0;

    /** @var int */
    private $coinFlipWon = 0;

    /** @var int */
    private $coinFlipLost = 0;

    /** @var int */
    private $coinFlipTotalWon = 0;

    /** @var int */
    private $coinFlipTotalLost = 0;

    /** @var int */
    private $executiveTimeLeft = 0;

    /** @var int */
    private $thresholdBonus = 0;

    /** @var string[] */
    private $itemSkins = [];

    /** @var array */
    private $currentItemSkin = ["", ""];

    /**
     * DataSession constructor.
     *
     * @param NexusPlayer $owner
     */
    public function __construct(NexusPlayer $owner) {
        $this->owner = $owner;
        $this->core = Nexus::getInstance();
        $this->load();
    }

    /**
     * @return NexusPlayer
     */
    public function getOwner(): NexusPlayer {
        return $this->owner;
    }

    /**
     * @param NexusPlayer $owner
     */
    public function setOwner(NexusPlayer $owner): void {
        $this->owner = $owner;
    }

    public function load(): void {
        $this->inbox = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $this->inbox->setName(TextFormat::YELLOW . "Inbox");
        $this->inbox->setListener(function(InvMenuTransaction $transaction): InvMenuTransactionResult {
            $itemClickedWith = $transaction->getItemClickedWith();
            $itemClicked = $transaction->getItemClicked();
            if($itemClickedWith->getId() !== ItemIds::AIR) {
                return $transaction->discard();
            }
            if($itemClicked->getId() === ItemIds::AIR) {
                return $transaction->discard();
            }
            return $transaction->continue();
        });
        $this->showcase = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $this->showcase->setName(TextFormat::YELLOW . $this->owner->getName() . "'s Showcase");
        $this->showcase->setListener(function(InvMenuTransaction $transaction): InvMenuTransactionResult {
            /** @var NexusPlayer $player */
            $player = $transaction->getPlayer();
            $itemClicked = $transaction->getItemClicked();
            $itemClickedWith = $transaction->getItemClickedWith();
            $slot = $transaction->getAction()->getSlot();
            if(!$itemClicked->isNull()) {
                if($player->getName() === $this->owner->getName()) {
                    $player->removeCurrentWindow();
                    $player->sendDelayedForm(new ShowcaseActionForm($itemClicked, $slot));
                }
                else {
                    $player->removeCurrentWindow();
                    $player->sendDelayedForm(new ItemInformationForm($itemClicked));
                }
            }
            else {
                if(!$itemClickedWith->isNull()) {
                    $player->removeCurrentWindow();
                    $player->sendDelayedForm(new ShowcaseConfirmationForm($itemClickedWith, $slot));
                }
            }
            return $transaction->discard();
        });
        $uuid = $this->owner->getUniqueId()->toString();
        $username = $this->owner->getName();
        $connector = Nexus::getInstance()->getMySQLProvider()->getConnector();
        $connector->executeSelect("SELECT rankId, permissions, permanentPermissions, votePoints, tags, currentTag, vaults, homes, showcases, showcase, inbox FROM players WHERE uuid = ?;", "s", [
            $uuid
        ], function(array $rows) {
            $start = microtime(true);
            if(empty($rows)) {
                $this->rank = $this->core->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::PLAYER);
            }
            else {
                foreach($rows as [
                        "rankId" => $rankId,
                        "permissions" => $permissions,
                        "permanentPermissions" => $permanentPermissions,
                        "votePoints" => $votePoints,
                        "tags" => $tags,
                        "currentTag" => $currentTag,
                        "vaults" => $vaults,
                        "homes" => $homes,
                        "showcases" => $showcases,
                        "showcase" => $showcase,
                        "inbox" => $inbox
                ]) {
                    $this->rank = $this->core->getPlayerManager()->getRankManager()->getRankByIdentifier($rankId);
                    $this->permissions = ArrayUtils::decodeArray($permissions);
                    $this->permanentPermissions = ArrayUtils::decodeArray($permanentPermissions);
                    $this->votePoints = $votePoints;
                    $this->tags = ArrayUtils::decodeArray($tags);
                    $this->currentTag = $currentTag;
                    $this->additionalVaults = $vaults;
                    $this->additionalHomes = $homes;
                    $this->additionalShowcases = $showcases;
                    $items = Nexus::decodeInventory($showcase);
                    $i = 0;
                    foreach($items as $item) {
                        $this->showcase->getInventory()->setItem($i, $item);
                        ++$i;
                    }
                    $items = Nexus::decodeInventory($inbox);
                    $i = 0;
                    foreach($items as $item) {
                        $this->inbox->getInventory()->setItem($i, $item);
                        ++$i;
                    }
                }
            }
            $rankId = $this->rank->getIdentifier();
            $this->updateNameTag();
//            $this->owner->setNameTag($this->rank->getTagFormatFor($this->owner, [
//                "gangRole" => $this->getGangRoleToString(),
//                "gang" => $this->gang instanceof Gang ? $this->gang->getName() . " " : "",
//                "level" => $this->getPrestigeTag()
//            ]));
            /** @var NexusPlayer $onlinePlayer */
            foreach($this->core->getServer()->getOnlinePlayers() as $onlinePlayer) {
                if($rankId >= Rank::TRAINEE and $rankId <= Rank::EXECUTIVE) {
                    break;
                }
                if($onlinePlayer->hasVanished()) {
                    $this->owner->hidePlayer($onlinePlayer);
                }
            }
            $time = (microtime(true) - $start);
            $this->core->getLogger()->notice("[Load 1] Completed in " . ($time >= 1 ? round($time, 3) . "s" : round($time * 1000) . "ms"));
            $this->successCount++;
        });
        $connector->executeSelect("SELECT gang, gangRole, xp, prestige, balance, blocks, bankBlock, kills, onlineTime, kitCooldowns, kitTiers, maxLevelObtained, drinkXPTime, xpDrank, maxDrinkableXP, lastOnlineTime, restedXP, quests, lastQuestReroll FROM stats WHERE uuid = ?;", "s", [
            $uuid
        ], function(array $rows) {
            $start = microtime(true);
            foreach($rows as [
                    "gang" => $gang,
                    "gangRole" => $gangRole,
                    "xp" => $xp,
                    "prestige" => $prestige,
                    "balance" => $balance,
                    "blocks" => $blocks,
                    "bankBlock" => $bankBlock,
                    "kills" => $kills,
                    "onlineTime" => $onlineTime,
                    "kitCooldowns" => $kitCooldowns,
                    "kitTiers" => $kitTiers,
                    "maxLevelObtained" => $maxLevelObtained,
                    "drinkXPTime"=> $drinkXPTime,
                    "xpDrank" => $xpDrank,
                    "maxDrinkableXP" => $maxDrinkableXP,
                    "lastOnlineTime" => $lastOnlineTime,
                    "restedXP" => $restedXP,
                    "quests" => $quests,
                    "lastQuestReroll" => $lastQuestReroll
            ]) {
                $gang = $gang !== null ? $this->core->getPlayerManager()->getGangManager()->getGang($gang) : null;
                if($gang !== null and (!$gang->isInGang($this->owner->getName()))) {
                    $gang = null;
                }
                $this->gang = $gang;
                $this->gangRole = $gang !== null ? $gangRole : null;
                $this->xp = $xp;
                $this->prestige = $prestige;
                $this->balance = $balance;
                $this->blocks = $blocks;
                $this->bankBlock = $bankBlock;
                $this->kills = $kills;
                $this->onlineTime = $onlineTime;
                $this->kitCooldowns = ArrayUtils::decodeArray($kitCooldowns);
                $this->kitTiers = ArrayUtils::decodeArray($kitTiers);
                $this->maxLevelObtained = $maxLevelObtained;
                $this->drinkXPTime = $drinkXPTime;
                $this->xpDrank = $xpDrank;
                $this->maxDrinkableXP = $maxDrinkableXP;
                $this->restedXP = min(14400, (floor((time() - $lastOnlineTime) / 6) + $restedXP));
                $this->quests = ArrayUtils::decodeArray($quests);
                $simple = [];
                $uncommon = [];
                $elite = [];
                $ultimate = [];
                $legendary = [];
                $godly = [];
                foreach($this->quests as $name => $progress) {
                    $quest = $this->core->getGameManager()->getQuestManager()->getQuest($name);
                    if($quest === null) {
                        continue;
                    }
                    switch($quest->getRarity()) {
                        case Rarity::SIMPLE:
                            $simple[$name] = $progress;
                            break;
                        case Rarity::UNCOMMON:
                            $uncommon[$name] = $progress;
                            break;
                        case Rarity::ELITE:
                            $elite[$name] = $progress;
                            break;
                        case Rarity::ULTIMATE:
                            $ultimate[$name] = $progress;
                            break;
                        case Rarity::LEGENDARY:
                            $legendary[$name] = $progress;
                            break;
                        case Rarity::GODLY:
                            $godly[$name] = $progress;
                            break;
                    }
                }
                $this->quests = array_merge($simple, $uncommon, $elite, $ultimate, $legendary, $godly);
                $this->lastQuestReroll = $lastQuestReroll;
            }
            $time = (microtime(true) - $start);
            $this->core->getLogger()->notice("[Load 2] Completed in " . ($time >= 1 ? round($time, 3) . "s" : round($time * 1000) . "ms"));
            $this->successCount++;
        });
        $connector->executeSelect("SELECT lotteryBought, lotteryWon, lotteryTotalWon, coinFlipWon, coinFlipLost, coinFlipTotalWon, coinFlipTotalLost FROM gamblingStats WHERE uuid = ?", "s", [
            $uuid
        ], function(array $rows) {
            $start = microtime(true);
            foreach($rows as [
                    "lotteryBought" => $lotteryBought,
                    "lotteryWon" => $lotteryWon,
                    "lotteryTotalWon" => $lotteryTotalWon,
                    "coinFlipWon" => $coinFlipWon,
                    "coinFlipLost" => $coinFlipLost,
                    "coinFlipTotalWon" => $coinFlipTotalWon,
                    "coinFlipTotalLost" => $coinFlipTotalLost
            ]) {
                $this->lotteryBought = $lotteryBought;
                $this->lotteryWon = $lotteryWon;
                $this->lotteryTotalWon = $lotteryTotalWon;
                $this->coinFlipWon = $coinFlipWon;
                $this->coinFlipLost = $coinFlipLost;
                $this->coinFlipTotalWon = $coinFlipTotalWon;
                $this->coinFlipTotalLost = $coinFlipTotalLost;
            }
            $time = (microtime(true) - $start);
            $this->core->getLogger()->notice("[Load 3] Completed in " . ($time >= 1 ? round($time, 3) . "s" : round($time * 1000) . "ms"));
            $this->successCount++;
        });
        $connector->executeSelect("SELECT energyModifier, energyTime, energyDuration, xpModifier, xpTime, xpDuration, executiveTime, executiveDuration FROM modifiers WHERE uuid = ?", "s", [
            $uuid
        ], function(array $rows) {
            $start = microtime(true);
            foreach($rows as [
                    "energyModifier" => $energyModifier,
                    "energyTime" => $energyTime,
                    "energyDuration" => $energyDuration,
                    "xpModifier" => $xpModifier,
                    "xpTime" => $xpTime,
                    "xpDuration" => $xpDuration,
                    "executiveTime" => $executiveTime,
                    "executiveDuration" => $executiveDuration
            ]) {
                $this->energyModifier = $energyModifier;
                $this->energyTime = $energyTime;
                $this->energyDuration = $energyDuration;
                $this->xpModifier = $xpModifier;
                $this->xpTime = $xpTime;
                $this->xpDuration = $xpDuration;
                $this->executiveTime = $executiveTime;
                $this->executiveDuration = $executiveDuration;
            }
            $time = (microtime(true) - $start);
            $this->core->getLogger()->notice("[Load 4] Completed in " . ($time >= 1 ? round($time, 3) . "s" : round($time * 1000) . "ms"));
            $this->successCount++;
        });
        $connector->executeSelect("SELECT name, x, y, z, level FROM homes WHERE uuid = ?;", "s", [
            $uuid
        ], function(array $rows) {
            $start = microtime(true);
            foreach($rows as [
                    "name" => $name,
                    "x" => $x,
                    "y" => $y,
                    "z" => $z,
                    "level" => $level
            ]) {
                $lvl = $this->core->getServer()->getWorldManager()->getWorldByName($level);
                if($lvl === null) {
                    continue;
                }
                $this->homes[$name] = new Position($x, $y, $z, $lvl);
            }
            $time = (microtime(true) - $start);
            $this->core->getLogger()->notice("[Load 5] Completed in " . ($time >= 1 ? round($time, 3) . "s" : round($time * 1000) . "ms"));
            ++$this->successCount;
        });
        $connector->executeSelect("SELECT uuid, id, items FROM vaults WHERE username = ?;", "s", [
            $username
        ], function(array $rows) {
            $start = microtime(true);
            $manager = Nexus::getInstance()->getPlayerManager()->getVaultManager();
            $duplicate = false;
            $maxUUIDs = [];
            foreach($rows as [
                    "uuid" => $uuid,
                    "id" => $id,
                    "items" => $items
            ]) {
                if(isset($maxUUIDs[$id]) and $maxUUIDs[$id] < $uuid) {
                    $duplicate = true;
                    $maxUUIDs[$id] = $uuid;
                }
                else {
                    $maxUUIDs[$id] = $uuid;
                }
                $vault = new Vault($this->owner->getName(), $uuid, $id, $items);
                $manager->addVault($vault);
            }
            if($duplicate) {
                $username = $this->owner->getName();
                $this->core->getLogger()->notice("[PV] Detected duplicated vaults from $username. Deleting duplicates now...");
                foreach($maxUUIDs as $vaultId => $uuid) {
                    $database = Nexus::getInstance()->getMySQLProvider()->getDatabase();
                    $stmt = $database->prepare("DELETE FROM vaults WHERE username = ? AND id = ? AND uuid <> ?");
                    $stmt->bind_param("sii", $username, $vaultId, $uuid);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $time = (microtime(true) - $start);
            $this->core->getLogger()->notice("[Load 6] Completed in " . ($time >= 1 ? round($time, 3) . "s" : round($time * 1000) . "ms"));
            ++$this->successCount;
        });
        $connector->executeSelect("SELECT duration, thresholdBonus FROM executive WHERE uuid = ?;", "s", [
            $uuid
        ], function(array $rows) {
            $start = microtime(true);
            foreach($rows as [
                    "duration" => $duration,
                    "thresholdBonus" => $thresholdBonus
            ]) {
                $this->executiveTimeLeft = $duration;
                $this->thresholdBonus = $thresholdBonus;
            }
            $time = (microtime(true) - $start);
            $this->core->getLogger()->notice("[Load 7] Completed in " . ($time >= 1 ? round($time, 3) . "s" : round($time * 1000) . "ms"));
            ++$this->successCount;
        });
        $connector->executeSelect("SELECT skins, currentSkin FROM itemSkins WHERE uuid  = ?;", "s", [
            $uuid
        ], function(array $rows) {
            $start = microtime(true);
            foreach($rows as [
                    "skins" => $skins,
                    "currentSkin" => $currentSkin
            ]) {
                $this->itemSkins = ArrayUtils::decodeArray($skins);
                $this->currentItemSkin = ArrayUtils::decodeArray($currentSkin);
            }
            ++$this->successCount;
            $time = (microtime(true) - $start);
            $this->core->getLogger()->notice("[Load 8] Completed in " . ($time >= 1 ? round($time, 3) . "s" : round($time * 1000) . "ms"));
        });
    }

    /**
     * @return bool
     */
    public function isLoaded(): bool {
        return $this->successCount === 8;
    }

    public function saveData(): void {
        $uuid = $this->owner->getUniqueId()->toString();
        $username = $this->owner->getName();
        $database = $this->core->getMySQLProvider()->getDatabase();
        $rank = $this->rank->getIdentifier();
        $permissions = ArrayUtils::encodeArray($this->permissions);
        $permanentPermissions = ArrayUtils::encodeArray($this->permanentPermissions);
        $tags = ArrayUtils::encodeArray($this->tags);
        $inbox = Nexus::encodeInventory($this->inbox->getInventory());
        $showcase = Nexus::encodeInventory($this->showcase->getInventory(), true);
        $stmt = $database->prepare("REPLACE INTO players(uuid, username, rankId, permissions, permanentPermissions, votePoints, tags, currentTag, vaults, homes, showcases, showcase, inbox) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssississiiiss", $uuid, $username, $rank, $permissions, $permanentPermissions, $this->votePoints, $tags, $this->currentTag, $this->additionalVaults, $this->additionalHomes, $this->additionalShowcases, $showcase, $inbox);
        $stmt->execute();
        $stmt->close();
        $stmt = $database->prepare("REPLACE INTO gamblingStats(uuid, username, lotteryBought, lotteryWon, lotteryTotalWon, coinFlipWon, coinFlipLost, coinFlipTotalWon, coinFlipTotalLost) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiiiiii", $uuid, $username, $this->lotteryBought, $this->lotteryWon, $this->lotteryTotalWon, $this->coinFlipWon, $this->coinFlipLost, $this->coinFlipTotalWon, $this->coinFlipTotalLost);
        $stmt->execute();
        $stmt->close();
        $gang = $this->gang !== null ? $this->gang->getName() : null;
        $kitCooldowns = ArrayUtils::encodeArray($this->kitCooldowns);
        $kitTiers = ArrayUtils::encodeArray($this->kitTiers);
        $lastOnlineTime = time();
        $restedXp = $this->getRestedXP();
        $quests = ArrayUtils::encodeArray($this->quests);
        $stmt = $database->prepare("REPLACE INTO stats(uuid, username, gang, gangRole, xp, prestige, balance, blocks, bankBlock, kills, onlineTime, kitCooldowns, kitTiers, maxLevelObtained, drinkXPTime, xpDrank, maxDrinkableXP, lastOnlineTime, restedXP, quests, lastQuestReroll) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiiiiiiiissiiiiiisi", $uuid, $username, $gang, $this->gangRole, $this->xp, $this->prestige, $this->balance, $this->blocks, $this->bankBlock, $this->kills, $this->onlineTime, $kitCooldowns, $kitTiers, $this->maxLevelObtained, $this->drinkXPTime, $this->xpDrank, $this->maxDrinkableXP, $lastOnlineTime, $restedXp, $quests, $this->lastQuestReroll);
        $stmt->execute();
        $stmt->close();
        $stmt = $database->prepare("REPLACE INTO modifiers(uuid, username, energyModifier, energyTime, energyDuration, xpModifier, xpTime, xpDuration, executiveTime, executiveDuration) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiidiiii", $uuid, $username, $this->energyModifier, $this->energyTime, $this->energyDuration, $this->xpModifier, $this->xpTime, $this->xpDuration, $this->executiveTime, $this->executiveDuration);
        $stmt->execute();
        $stmt->close();
        $stmt = $database->prepare("REPLACE INTO executive(uuid, duration, thresholdBonus) VALUES(?, ?, ?)");
        $stmt->bind_param("sii", $uuid, $this->executiveTimeLeft, $this->thresholdBonus);
        $stmt->execute();
        $stmt->close();
        $itemSkins = ArrayUtils::encodeArray($this->itemSkins);
        $currentItemSkins = ArrayUtils::encodeArray($this->currentItemSkin);
        $stmt = $database->prepare("REPLACE INTO itemSkins(uuid, skins, currentSkin) VALUES(?, ?, ?)");
        $stmt->bind_param("sss", $uuid, $itemSkins, $currentItemSkins);
        $stmt->execute();
        $stmt->close();
    }

    public function saveDataAsync(): void {
        $uuid = $this->owner->getUniqueId()->toString();
        $username = $this->owner->getName();
        $connector = $this->core->getMySQLProvider()->getConnector();
        $rank = $this->rank->getIdentifier();
        $permissions = ArrayUtils::encodeArray($this->permissions);
        $permanentPermissions = ArrayUtils::encodeArray($this->permanentPermissions);
        $tags = ArrayUtils::encodeArray($this->tags);
        $inbox = Nexus::encodeInventory($this->inbox->getInventory());
        $showcase = Nexus::encodeInventory($this->showcase->getInventory());
        $connector->executeUpdate("REPLACE INTO players(uuid, username, rankId, permissions, permanentPermissions, votePoints, tags, currentTag, vaults, homes, showcases, showcase, inbox) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", "ssississiiiss", [
            $uuid,
            $username,
            $rank,
            $permissions,
            $permanentPermissions,
            $this->votePoints,
            $tags,
            $this->currentTag,
            $this->additionalVaults,
            $this->additionalHomes,
            $this->additionalShowcases,
            $showcase,
            $inbox
        ]);
        $gang = $this->gang !== null ? $this->gang->getName() : null;
        $kitCooldowns = ArrayUtils::encodeArray($this->kitCooldowns);
        $kitTiers = ArrayUtils::encodeArray($this->kitTiers);
        $connector->executeUpdate("REPLACE INTO gamblingStats(uuid, username, lotteryBought, lotteryWon, lotteryTotalWon, coinFlipWon, coinFlipLost, coinFlipTotalWon, coinFlipTotalLost) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)", "ssiiiiiii", [
            $uuid,
            $username,
            $this->lotteryBought,
            $this->lotteryWon,
            $this->lotteryTotalWon,
            $this->coinFlipWon,
            $this->coinFlipLost,
            $this->coinFlipTotalWon,
            $this->coinFlipTotalLost
        ]);
        $connector->executeUpdate("REPLACE INTO stats(uuid, username, gang, gangRole, xp, prestige, balance, blocks, bankBlock, kills, onlineTime, kitCooldowns, kitTiers, maxLevelObtained, drinkXPTime, xpDrank, maxDrinkableXP, lastOnlineTime, restedXP, quests, lastQuestReroll) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", "sssiiiiiiiissiiiiiisi", [
            $uuid,
            $username,
            $gang,
            $this->gangRole,
            $this->xp,
            $this->prestige,
            $this->balance,
            $this->blocks,
            $this->bankBlock,
            $this->kills,
            $this->onlineTime,
            $kitCooldowns,
            $kitTiers,
            $this->maxLevelObtained,
            $this->drinkXPTime,
            $this->xpDrank,
            $this->maxDrinkableXP,
            time(),
            $this->getRestedXP(),
            ArrayUtils::encodeArray($this->quests),
            $this->lastQuestReroll
        ]);
        $connector->executeUpdate("REPLACE INTO modifiers(uuid, username, energyModifier, energyTime, energyDuration, xpModifier, xpTime, xpDuration, executiveTime, executiveDuration) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", "ssdiidiiii", [
            $uuid,
            $username,
            $this->energyModifier,
            $this->energyTime,
            $this->energyDuration,
            $this->xpModifier,
            $this->xpTime,
            $this->xpDuration,
            $this->executiveTime,
            $this->executiveDuration
        ]);
        $connector->executeUpdate("REPLACE INTO executive(uuid, duration, thresholdBonus) VALUES(?, ?, ?)", "sii", [
            $uuid,
            $this->executiveTimeLeft,
            $this->thresholdBonus
        ]);
        $connector->executeUpdate("REPLACE INTO itemSkins(uuid, skins, currentSkin) VALUES (?, ?, ?)", "sss", [
            $uuid,
            ArrayUtils::encodeArray($this->itemSkins),
            ArrayUtils::encodeArray($this->currentItemSkin)
        ]);
    }

    /**
     * @return string[]
     */
    public function getItemSkins() : array {
        return $this->itemSkins;
    }

    /**
     * @return array
     */
    public function getCurrentItemSkin(): array {
        return $this->currentItemSkin !== null ? $this->currentItemSkin : [];
    }

    /**
     * @param string $tag
     */
    public function setCurrentItemSkin(string $skin, int $type): void {
        if($type === BlockToolType::SWORD) {
            $this->currentItemSkin[0] = $skin;
        } elseif($type === BlockToolType::PICKAXE) {
            $this->currentItemSkin[1] = $skin;
        }
        NexusListener::parseInventory($this->getOwner());
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function hasItemSkin(string $skin): bool {
        return in_array($skin, $this->itemSkins);
    }

    /**
     * @param string $tag
     */
    public function addItemSkin(string $skin): void {
        if(in_array($skin, $this->itemSkins)) {
            return;
        }
        $this->itemSkins[] = $skin;
    }

    public function canEnterExecutiveMine() : bool {
        return $this->executiveTimeLeft > 0;
    }

    public const BLOCKS_PER_MINUTE = 100;

    public function getThresholdUpgradeQuota() : int {
        return self::BLOCKS_PER_MINUTE * (10+($this->thresholdBonus/60));
    }

    public function getExecutiveMineTimeInSeconds() : int {
        if($this->executiveTimeLeft === 0) {
            return 0;
        }
        return $this->executiveTimeLeft + $this->thresholdBonus;
    }

    public function getFancyExecutiveTime(string $xuid) : int {
        if(isset(WarpMenuInventory::$executiveSessions[$xuid])){
            return WarpMenuInventory::$executiveSessions[$xuid]->getTimeLeft();
        }
        return $this->getExecutiveMineTimeInSeconds();
    }

    public function clearExecutiveMineTime() : void {
        $this->executiveTimeLeft = 0;
    }

    public function addThreshold(int $seconds) : void {
        $this->thresholdBonus += $seconds;
        $this->thresholdBonus = min(1200, $this->thresholdBonus);
    }

    public function reduceThreshold(int $seconds) : void {
        $this->thresholdBonus -= $seconds;
        $this->thresholdBonus = max(0, $this->thresholdBonus);
    }

    public function resetExecutiveMineTime() : void {
        $this->executiveTimeLeft = 600;
    }

    /**
     * @param Kit $kit
     *
     * @return int
     */
    public function getKitCooldown(Kit $kit): int {
        if(!isset($this->kitCooldowns[$kit->getName()])) {
            $this->kitCooldowns[$kit->getName()] = 0;
            return 0;
        }
        return $this->kitCooldowns[$kit->getName()];
    }

    /**
     * @param Kit $kit
     */
    public function setKitCooldown(Kit $kit): void {
        $this->kitCooldowns[$kit->getName()] = time();
    }

    /**
     * @param GodKit $kit
     *
     * @return int
     */
    public function getGodKitTier(GodKit $kit): int {
        if(!isset($this->kitTiers[$kit->getName()])) {
            $this->kitTiers[$kit->getName()] = 0;
            return 0;
        }
        return $this->kitTiers[$kit->getName()];
    }

    /**
     * @param GodKit $kit
     */
    public function levelUpGodKitTier(GodKit $kit): void {
        ++$this->kitTiers[$kit->getName()];
    }

    /**
     * @return int
     */
    public function getXP(): int {
        return (int) floor($this->xp);
    }

    /**
     * @return Gang|null
     */
    public function getGang(): ?Gang {
        return $this->gang;
    }

    /**
     * @param Gang|null $gang
     */
    public function setGang(?Gang $gang): void {
        $this->gang = $gang;
        if($this->owner->getDisguiseRank() !== null) {
            $this->owner->setNameTag($this->owner->getDisguiseRank()->getTagFormatFor($this->owner, [
                "gangRole" => "",
                "gang" => "",
                "level" => $this->getPrestigeTag()
            ]));
            return;
        }
        $this->updateNameTag();
//        $this->owner->setNameTag($this->rank->getTagFormatFor($this->owner, [
//            "gangRole" => $this->getGangRoleToString(),
//            "gang" => $this->gang instanceof Gang ? $this->gang->getName() . " " : "",
//            "level" => $this->getPrestigeTag()
//        ]));
    }

    /**
     * @return int|null
     */
    public function getGangRole(): ?int {
        return $this->gangRole;
    }

    /**
     * @param int|null $gangRole
     */
    public function setGangRole(?int $gangRole): void {
        $this->gangRole = $gangRole;
        if($this->owner->getDisguiseRank() !== null) {
            $this->owner->setNameTag($this->owner->getDisguiseRank()->getTagFormatFor($this->owner, [
                "gangRole" => "",
                "gang" => "",
                "level" => $this->getPrestigeTag()
            ]));
            return;
        }
        $this->updateNameTag();
//        $this->owner->setNameTag($this->rank->getTagFormatFor($this->owner, [
//            "gangRole" => $this->getGangRoleToString(),
//            "gang" => $this->gang instanceof Gang ? $this->gang->getName() . " " : "",
//            "level" => $this->getPrestigeTag()
//        ]));
    }

    /**
     * @return int
     */
    public function getBalance(): int {
        return (int)$this->balance;
    }

    /**
     * @param float $amount
     */
    public function setBalance(float $amount): void {
        $this->balance = $amount;
        $this->owner->getScoreboard()->setScoreLine(5, TextFormat::WHITE . "   $" . number_format($this->balance, 2));
    }

    /**
     * @param float $amount
     * @param bool $message
     */
    public function addToBalance(float $amount, bool $message = true): void {
        $this->balance += $amount;
        if($message) {
            $this->owner->sendMessage(TextFormat::BOLD . TextFormat::GREEN . " + $" . number_format($amount, 2));
            $this->owner->getScoreboard()->setScoreLine(5, TextFormat::WHITE . "   $" . number_format($this->balance, 2));
        }
    }

    /**
     * @param float $amount
     * @param bool $message
     */
    public function subtractFromBalance(float $amount, bool $message = true): void {
        $this->balance -= $amount;
        if($message) {
            $this->owner->sendMessage(TextFormat::BOLD . TextFormat::RED . " - $" . number_format($amount, 2));
            $this->owner->getScoreboard()->setScoreLine(5, TextFormat::WHITE . "   $" . number_format($this->balance, 2));
        }
    }

    /**
     * @return int
     */
    public function getBankBlock(): int {
        return $this->bankBlock;
    }

    /**
     * @param int $bankBlock
     */
    public function setBankBlock(int $bankBlock): void {
        $this->bankBlock = min($this->getBankBlockLimit(), $bankBlock);
    }

    /**
     * @return int
     */
    public function getBankBlockLimit(): int {
        $level = $this->getTotalXPLevel();
        $amount = 0;
        $multiplier = $this->prestige > 0 ? (2 ** ($this->prestige + 1)) : 1;
        for($i = 0; $i <= $level; $i++) {
            if($i <= 10) {
                $amount += (5000 * $multiplier);
            }
            elseif($i <= 20) {
                $amount += (10000 * $multiplier);
            }
            elseif($i <= 30) {
                $amount += (15000 * $multiplier);
            }
            elseif($i <= 40) {
                $amount += (25000 * $multiplier);
            }
            elseif($i <= 50) {
                $amount += (50000 * $multiplier);
            }
            elseif($i <= 60) {
                $amount += (100000 * $multiplier);
            }
            elseif($i <= 70) {
                $amount += (250000 * $multiplier);
            }
            elseif($i <= 80) {
                $amount += (750000 * $multiplier);
            }
            elseif($i <= 90) {
                $amount += (2750000 * $multiplier);
            }
            elseif($i <= 100) {
                $amount += (5500000 * $multiplier);
            }
        }
        return $amount;
    }

    /**
     * @param int $amount
     */
    public function addBankBlock(int $amount): void {
        $this->bankBlock += $amount;
        $this->bankBlock = min($this->getBankBlockLimit(), $this->bankBlock);
    }

    /**
     * @return Rank
     */
    public function getRank(): Rank {
        return $this->rank;
    }

    /**
     * @param Rank $rank
     *
     * @throws UtilsException
     */
    public function setRank(Rank $rank): void {
        $this->rank = $rank;
        if($this->owner->getScoreboard()->isSpawned()) {
            $this->owner->getScoreboard()->setScoreLine(2, $this->rank->getColoredName());
        }
        if($this->owner->getDisguiseRank() !== null) {
            $this->owner->setNameTag($this->owner->getDisguiseRank()->getTagFormatFor($this->owner, [
                "gangRole" => "",
                "gang" => "",
                "level" => $this->getPrestigeTag()
            ]));
            return;
        }
//        $this->owner->setNameTag($rank->getTagFormatFor($this->owner, [
//            "gangRole" => $this->getGangRoleToString(),
//            "gang" => $this->gang instanceof Gang ? $this->gang->getName() . " " : "",
//            "level" => $this->getPrestigeTag()
//        ]));
        $this->updateNameTag();
    }

    /**
     * @return string
     */
    public function getGangRoleToString(): string {
        switch($this->gangRole) {
            case Gang::RECRUIT:
                return "";
                break;
            case Gang::MEMBER:
                return "*";
                break;
            case Gang::OFFICER:
                return "**";
                break;
            case Gang::LEADER:
                return "***";
                break;
            default:
                return "";
        }
    }

    /**
     * @return Vault[]
     */
    public function getVaults(): array {
        return $this->core->getPlayerManager()->getVaultManager()->getVaultsFor($this->owner->getName());
    }

    /**
     * @param int $id
     *
     * @return Vault|null
     */
    public function getVaultById(int $id): ?Vault {
        $vault = $this->core->getPlayerManager()->getVaultManager()->getVault($this->owner->getName(), $id);
        if($id <= $this->rank->getVaultsLimit($this->owner) and $vault === null) {
            $this->core->getPlayerManager()->getVaultManager()->createVault($this->owner->getName(), $id);
            return null;
        }
        return $vault;
    }

    /**
     * @param string $permission
     */
    public function addPermission(string $permission): void {
        if(in_array($permission, $this->permissions)) {
            return;
        }
        $this->permissions[] = $permission;
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array {
        return $this->permissions;
    }

    /**
     * @param string $permission
     */
    public function addPermanentPermission(string $permission): void {
        if(in_array($permission, $this->permanentPermissions)) {
            return;
        }
        $this->permanentPermissions[] = $permission;
    }

    /**
     * @return string[]
     */
    public function getPermanentPermissions(): array {
        return $this->permanentPermissions;
    }

    /**
     * @return array
     */
    public function getTags(): array {
        return $this->tags;
    }

    /**
     * @return string
     */
    public function getCurrentTag(): string {
        return $this->currentTag !== null ? $this->currentTag : "";
    }

    /**
     * @param string $tag
     */
    public function setCurrentTag(string $tag): void {
        $this->currentTag = $tag;
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function hasTag(string $tag): bool {
        return in_array($tag, $this->tags);
    }

    /**
     * @param string $tag
     */
    public function addTag(string $tag): void {
        if(in_array($tag, $this->tags)) {
            return;
        }
        $this->tags[] = $tag;
    }

    /**
     * @param int $amount
     */
    public function addKills(int $amount = 1): void {
        $this->kills += $amount;
    }

    /**
     * @return int
     */
    public function getKills(): int {
        return $this->kills;
    }

    /**
     * @param int $amount
     */
    public function addBlocksMined(int $amount = 1): void {
        $this->blocks += $amount;
    }

    /**
     * @return int
     */
    public function getBlocksMined(): int {
        return $this->blocks;
    }

    /**
     * @return Position[]
     */
    public function getHomes(): array {
        return $this->homes;
    }

    /**
     * @param Position[] $homes
     */
    public function setHomes(array $homes): void {
        $this->homes = $homes;
    }

    /**
     * @param string $name
     *
     * @return null|Position
     */
    public function getHome(string $name): ?Position {
        return isset($this->homes[$name]) ? Position::fromObject($this->homes[$name]->add(0.5, 0, 0.5), $this->homes[$name]->getWorld()) : null;
    }

    /**
     * @param string $name
     * @param Position $position
     */
    public function addHome(string $name, Position $position): void {
        $uuid = $this->owner->getUniqueId()->toString();
        $username = $this->owner->getName();
        $x = $position->getFloorX();
        $y = $position->getFloorY();
        $z = $position->getFloorZ();
        $level = $position->getWorld()->getFolderName();
        $this->homes[$name] = $position;
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("INSERT INTO homes(uuid, username, name, x, y, z, level) VALUES(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiiis", $uuid, $username, $name, $x, $y, $z, $level);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param string $name
     */
    public function deleteHome(string $name): void {
        $uuid = $this->owner->getUniqueId()->toString();
        unset($this->homes[$name]);
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("DELETE FROM homes WHERE uuid = ? AND name = ?");
        $stmt->bind_param("ss", $uuid, $name);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return int
     */
    public function getAdditionalVaults(): int {
        return $this->additionalVaults;
    }

    /**
     * @param int $amount
     */
    public function addAdditionalVaults(int $amount = 1): void {
        $this->additionalVaults += $amount;
        $this->additionalHomes = min($this->additionalHomes, RankManager::MAX_VAULTS);
    }

    /**
     * @return int
     */
    public function getAdditionalHomes(): int {
        return $this->additionalHomes;
    }
    /**
     * @param int $amount
     */
    public function addAdditionalHomes(int $amount = 1): void {
        $this->additionalHomes += $amount;
        $this->additionalHomes = min($this->additionalHomes, RankManager::MAX_HOMES);
    }

    /**
     * @return int
     */
    public function getAdditionalShowcases(): int {
        return $this->additionalShowcases;
    }

    /**
     * @param int $amount
     */
    public function addAdditionalShowcases(int $amount = 1): void {
        $this->additionalShowcases += $amount;
        $this->additionalHomes = min($this->additionalHomes, RankManager::MAX_SHOWCASES);
    }

    /**
     * @param Item $item
     */
    public function addToInbox(Item $item): void {
        if($this->inbox->getInventory()->firstEmpty() === -1) {
            $this->owner->sendPopupTo(TextFormat::DARK_RED . "Full Inventory\n" . TextFormat::RED . "Clear out your inbox inventory to receive more!");
            return;
        }
        $this->inbox->getInventory()->setItem($this->inbox->getInventory()->firstEmpty(), $item);
    }

    /**
     * @return InvMenu
     */
    public function getInbox(): InvMenu {
        return $this->inbox;
    }

    /**
     * @param Item $item
     */
    public function addToShowcase(Item $item): void {
        if($this->showcase->getInventory()->firstEmpty() === -1) {
            return;
        }
        $this->showcase->getInventory()->setItem($this->inbox->getInventory()->firstEmpty(), $item);
    }

    /**
     * @return InvMenu
     */
    public function getShowcase(): InvMenu {
        return $this->showcase;
    }

    /**
     * @return int
     */
    public function getTotalXPLevel(): int {
        return XPUtils::xpToLevel($this->xp);
    }

    /**
     * @return int
     */
    public function getPrestige(): int {
        return $this->prestige;
    }

    public function prestige(): void {
        if($this->prestige >= RPGManager::MAX_PRESTIGE) {
            return;
        }
        ++$this->prestige;
        $this->owner->getServer()->broadcastMessage(TextFormat::BOLD . TextFormat::AQUA . " * " . TextFormat::WHITE . $this->owner->getName() . TextFormat::GOLD . " PRESTIGED TO " . TextFormat::LIGHT_PURPLE . "<" . TextFormat::AQUA . EnchantmentManager::getRomanNumber($this->prestige) . TextFormat::LIGHT_PURPLE . ">" . TextFormat::AQUA . " * ");
        $this->xp = 0;
        $this->maxLevelObtained = 0;
        $this->owner->getBossBar()->update($this->getLevelTag(), 0);
//        $this->owner->setNameTag($this->rank->getTagFormatFor($this->owner, [
//            "gangRole" => $this->getGangRoleToString(),
//            "gang" => $this->gang instanceof Gang ? $this->gang->getName() . " " : "",
//            "level" => $this->getPrestigeTag()
//        ]));
        $this->updateNameTag();
        $this->owner->getScoreboard()->setScoreLine(7, "   " . $this->getLevelTag() . TextFormat::GRAY . " (" . number_format((int)$this->xp) . ")");
        $nextLevel = (XPUtils::xpToLevel($this->getXP()) + 1) <= 100 ? (XPUtils::xpToLevel($this->getXP()) + 1) : "prestige";
        $this->owner->getScoreboard()->setScoreLine(9, TextFormat::GRAY . "   " . Utils::shrinkNumber((int)XPUtils::getNeededXP($this->xp, $this->prestige)) . "(" . TextFormat::GREEN . XPUtils::getXPProgress($this->xp, $this->prestige, RPGManager::MODIFIER, 1) . "%%%%" . TextFormat::GRAY . ") to " . TextFormat::WHITE . $nextLevel);
        $this->owner->playBlastSound();
        $this->owner->getCore()->getScheduler()->scheduleDelayedTask(new class($this->owner) extends Task {

            /** @var NexusPlayer */
            private $player;

            /**
             *  constructor.
             *
             * @param NexusPlayer $player
             */
            public function __construct(NexusPlayer $player) {
                $this->player = $player;
            }

            /**
             * @param int $currentTick
             */
            public function onRun(): void {
                if($this->player->isOnline() === false) {
                    return;
                }
                $this->player->playTwinkleSound();
            }
        }, 20);
    }

    /**
     * @param int $level
     *
     * @return string
     */
    public function getLevelTag(int $level = -1): string {
        if($level === -1) {
            $level = XPUtils::xpToLevel($this->xp);
        }
        if($this->prestige <= 0) {
            return TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . $level . TextFormat::RESET;
        }
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "<" . TextFormat::AQUA . EnchantmentManager::getRomanNumber($this->prestige) . TextFormat::LIGHT_PURPLE . "> " . TextFormat::WHITE . $level . TextFormat::RESET;
    }

    /**
     * @return string
     */
    public function getPrestigeTag(): string {
        if($this->prestige <= 0) {
            return "";
        }
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "<" . TextFormat::AQUA . EnchantmentManager::getRomanNumber($this->prestige) . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . " ";
    }


    /**
     * @param int $amount
     * @param bool $modify
     *
     * @throws AnimationException
     * @throws TranslationException
     */
    public function addToXP(int|float $amount, bool $modify = true): void {
        $amount = (int) $amount;
        $item = $this->owner->getInventory()->getItemInHand();
        if($modify) {
            $amount = (int)($amount * $this->getXPModifier());
            if($item instanceof Pickaxe) {
                $warpMiner = $item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::WARP_MINER));
                if($warpMiner > 0 and ($item->getWarpMinerMined() < (($warpMiner * 2000)))) {
                    $amount *= $warpMiner + 10;
                }
                $timeWarp = $item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::TIME_WARP));
                if($timeWarp > 0 and ($item->getWarpMinerMined() < (($timeWarp * 4000)))) {
                    $amount *= ($timeWarp * 5) + 16;
                }
                if($item->getAttribute(Pickaxe::XP_MASTERY) > 0) {
                    $amount *= 1 + ($item->getAttribute(Pickaxe::XP_MASTERY) / 100);
                }
            }
            $helmet = $this->owner->getArmorInventory()->getHelmet();
            if($helmet instanceof Armor) {
                if($helmet->hasMask(Mask::GUARD)) {
                    $amount *= 1.1;
                }
            }
        }

        $multi = 1;

        if($item instanceof Pickaxe) {
            foreach ($this->owner->getArmorInventory()->getContents() as $armor) {
                if($armor instanceof Armor) {
                    $level = $armor->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::EXPERIENCE_ABSORPTION));

                    if($level > 0) {
                        $multi += 0.0125 * $level;
                    }
                }
            }
        }

        $amount *= $multi;

        $cap = RPGManager::getLevelCapXP();
        if($cap <= ($amount + $this->xp)) {
            $amount = $cap - $this->xp;
        }

        if($amount > 0 and ($this->xp + $amount) === $cap) {
            $this->owner->playBlastSound();
            $this->owner->sendTitle(TextFormat::BOLD . TextFormat::DARK_AQUA . "(!) Notice", TextFormat::GRAY . "You have reached /levelcap!", 20, 100, 20);
        }

        $oldLevel = XPUtils::xpToLevel($this->xp);
        $newLevel = XPUtils::xpToLevel($this->xp + $amount);
        if($newLevel > 100) {
            $newLevel = 100;
        }
        if($amount > 0) {
            if($item instanceof Pickaxe) {
                if($item->getAttribute(Pickaxe::INQUISITIVE) > 0) {
                    if(mt_rand(1, 100) === 1) {
                        $add = ($item->getAttribute(Pickaxe::INQUISITIVE) / 100) * $amount;
                        $this->owner->addItem((new XPBottle((int)$add, ItemManager::getRarityForXPByLevel($this->getTotalXPLevel())))->toItem()->setCount(1), true);
                    }
                }
            }
        }
        $this->xp += $amount;
        if($this->xp > (XPUtils::levelToXP(100) + RPGManager::getPrestigeXP($this->prestige))) {
            $this->xp = (XPUtils::levelToXP(100) + RPGManager::getPrestigeXP($this->prestige));
        }
        $percent = $this->owner->getBossBar()->getHealthPercent();
        $nextPercent = XPUtils::getXPProgress($this->xp, $this->prestige, RPGManager::MODIFIER, 0);
        if($nextPercent !== $percent) {
            $this->owner->getBossBar()->update($this->getLevelTag() . TextFormat::YELLOW . " ($nextPercent%%%%)", $nextPercent);
        }
        $this->owner->getScoreboard()->setScoreLine(7, "   " . $this->getLevelTag() . TextFormat::GRAY . " (" . number_format((int)$this->xp) . ")");
        $nextLevel = (XPUtils::xpToLevel($this->getXP()) + 1) <= 100 ? (XPUtils::xpToLevel($this->getXP()) + 1) : "prestige";
        $this->owner->getScoreboard()->setScoreLine(9, TextFormat::GRAY . "   " . Utils::shrinkNumber((int)XPUtils::getNeededXP($this->xp, $this->prestige)) . "(" . TextFormat::GREEN . XPUtils::getXPProgress($this->xp, $this->prestige, RPGManager::MODIFIER, 1) . "%%%%" . TextFormat::GRAY . ") to " . TextFormat::WHITE . $nextLevel);
        if($newLevel > $oldLevel) {
            $this->core->getPlayerManager()->getRPGManager()->handleLevelUp($this->owner, $oldLevel, $newLevel);
//            $this->owner->setNameTag($this->rank->getTagFormatFor($this->owner, [
//                "gangRole" => $this->getGangRoleToString(),
//                "gang" => $this->gang instanceof Gang ? $this->gang->getName() . " " : "",
//                "level" => $this->getPrestigeTag()
//            ]));
            $this->updateNameTag();
            return;
        }
    }

    /**
     * @param int $amount
     */
    public function subtractFromXP(int $amount): void {
        $this->xp -= min($amount, $this->getMaxSubtractableXP());
        $percent = $this->owner->getBossBar()->getHealthPercent();
        $nextPercent = XPUtils::getXPProgress($this->xp, $this->prestige, RPGManager::MODIFIER, 0);
        if($nextPercent !== $percent) {
            $this->owner->getBossBar()->update($this->getLevelTag() . TextFormat::YELLOW . " ($nextPercent%%%%)", $nextPercent);
        }
        $this->owner->getScoreboard()->setScoreLine(7, "   " . $this->getLevelTag() . TextFormat::GRAY . " (" . number_format((int)$this->xp) . ")");
        $nextLevel = (XPUtils::xpToLevel($this->getXP()) + 1) <= 100 ? (XPUtils::xpToLevel($this->getXP()) + 1) : "prestige";
        $this->owner->getScoreboard()->setScoreLine(9, TextFormat::GRAY . "   " . Utils::shrinkNumber((int)XPUtils::getNeededXP($this->xp, $this->prestige)) . "(" . TextFormat::GREEN . XPUtils::getXPProgress($this->xp, $this->prestige, RPGManager::MODIFIER, 1) . "%%%%" . TextFormat::GRAY . ") to " . TextFormat::WHITE . $nextLevel);
//        $this->owner->setNameTag($this->rank->getTagFormatFor($this->owner, [
//            "gangRole" => $this->getGangRoleToString(),
//            "gang" => $this->gang instanceof Gang ? $this->gang->getName() . " " : "",
//            "level" => $this->getPrestigeTag()
//        ]));
        $this->updateNameTag();
    }

    /**
     * @return float
     */
    public function getBaseXPModifier(): float {
        if((time() - $this->xpTime) < $this->xpDuration) {
            return $this->xpModifier;
        }
        return 1.0;
    }

    /**
     * @return int
     */
    public function getXPBoostTimeLeft(): int {
        return ($this->xpDuration - (time() - $this->xpTime)) > 0 ? ($this->xpDuration - (time() - $this->xpTime)) : 0;
    }

    /**
     * @return float
     */
    public function getXPModifier(): float {
        $armorMulti = SetUtils::isWearingFullSet($this->getOwner(), "demolition");
        $itemMulti = $this->getOwner()->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "") === "demolition";
        $armorSantaMulti = SetUtils::isWearingFullSet($this->getOwner(), "santa");
        $itemSantiMulti = $this->getOwner()->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "ghost");

        if($this->restedXP > 0) {
            $subtractTime = time() - $this->loadTime;
            $this->restedXP = max(0, ($this->restedXP - $subtractTime));
            $multiplier = 1.0;
            if($this->restedXP > 0) {
                $multiplier = 1.5;
            }
            if((time() - $this->xpTime) < $this->xpDuration) {
                $multiplier *= $this->xpModifier;
            }
            if($armorMulti) $multiplier += 0.1;
            if($armorMulti && $itemMulti) $multiplier += 0.1;
            if($armorSantaMulti) $multiplier += 0.2;
            if($armorSantaMulti && $itemSantiMulti) $multiplier += 0.05;
            return $multiplier;
        }

        $res1 = $this->xpModifier;
        $res2 = 1.0;

        if($armorMulti) {
            $res1 += 0.1;
            $res2 += 0.1;

            if($itemMulti) {
                $res1 += 0.1;
                $res2 += 0.1;
            }
        }

        if($armorSantaMulti) {
            $res1 += 0.2;
            $res2 += 0.2;

            if($itemSantiMulti) {
                $res1 += 0.05;
                $res2 += 0.05;
            }
        }

        return (time() - $this->xpTime) < $this->xpDuration ? $res1 : $res2;
    }

    /**
     * @param float $xpModifier
     * @param int $duration
     */
    public function setXPModifier(float $xpModifier, int $duration = 0): void {
        $this->xpModifier = $xpModifier;
        $this->xpTime = time();
        $this->xpDuration = $duration;
    }

    /**
     * @return float
     */
    public function getEnergyModifier(): float {
        $res1 = $this->energyModifier;
        $res2 = 1.0;

        if(SetUtils::isWearingFullSet($this->getOwner(), "santa")) {
            $res1 += 0.1;
            $res2 += 0.1;

            if($this->getOwner()->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "") === "santa") {
                $res1 += 0.025;
                $res2 += 0.025;
            }
        }

        return (time() - $this->energyTime) < $this->energyDuration ? $res1 : $res2;
    }

    /**
     * @return int
     */
    public function getEnergyBoostTimeLeft(): int {
        return ($this->energyDuration - (time() - $this->energyTime)) > 0 ? ($this->energyDuration - (time() - $this->energyTime)) : 0;
    }

    /**
     * @param float $energyModifier
     * @param int $duration
     */
    public function setEnergyModifier(float $energyModifier, int $duration = 0): void {
        $this->energyModifier = $energyModifier;
        $this->energyTime = time();
        $this->energyDuration = $duration;
    }

    /**
     * @return int
     */
    public function getExecutiveBoostTimeLeft(): int {
        return ($this->executiveDuration - (time() - $this->executiveTime)) > 0 ? ($this->executiveDuration - (time() - $this->executiveTime)) : 0;
    }

    /**
     * @param int $duration
     */
    public function addExecutiveBoost(int $duration = 0): void {
        $this->executiveTime = time();
        $this->executiveDuration = $duration;
    }

    /**
     * @return int
     */
    public function getVotePoints(): int {
        return $this->votePoints;
    }

    /**
     * @param int $amount
     */
    public function addVotePoints(int $amount = 1) {
        $this->votePoints += $amount;
    }

    /**
     * @param int $amount
     */
    public function subtractVotePoints(int $amount) {
        $this->votePoints -= $amount;
    }

    /**
     * @param int $loadTime
     */
    public function setLoadTime(int $loadTime): void {
        $this->loadTime = $loadTime;
    }

    /**
     * @param int $maxLevelObtained
     */
    public function setMaxLevelObtained(int $maxLevelObtained): void {
        $this->maxLevelObtained = $maxLevelObtained;
    }

    /**
     * @return int
     */
    public function getMaxLevelObtained(): int {
        return $this->maxLevelObtained;
    }

    /**
     * @return int
     */
    public function getXPDrank(): int {
        return $this->xpDrank;
    }

    /**
     * @return int
     */
    public function getMaxDrinkableXP(): int {
        if((time() - $this->drinkXPTime) > 86400) {
            $this->drinkXPTime = time();
            $this->xpDrank = 0;
        }
        if($this->xpDrank === 0) {
            if($this->getTotalXPLevel() > 97) {
                $factor = XPUtils::levelToXP(100) - $this->xp;
                $this->maxDrinkableXP = $factor + RPGManager::getPrestigeXP($this->prestige);
            }
            else {
                $this->maxDrinkableXP = XPUtils::levelToXP($this->getTotalXPLevel() + 3) - $this->xp;
            }
        }
        return (int) floor($this->maxDrinkableXP);
    }

    /**
     * @return int
     */
    public function getDrinkXPTime(): int {
        return $this->drinkXPTime;
    }

    /**
     * @param int $xp
     *
     * @return int Leftover xp
     */
    public function drink(int $xp): int {
        if((time() - $this->drinkXPTime) > 86400) {
            $this->drinkXPTime = time();
            $this->xpDrank = 0;
        }
        if($this->xpDrank === 0) {
            if($this->getTotalXPLevel() > 97) {
                $factor = XPUtils::levelToXP(100) - $this->xp;
                $this->maxDrinkableXP = $factor + RPGManager::getPrestigeXP($this->prestige);
            }
            else {
                $this->maxDrinkableXP = XPUtils::levelToXP($this->getTotalXPLevel() + 3) - $this->xp;
            }
            if($this->maxDrinkableXP < $xp) {
                $xpToAdd = $this->maxDrinkableXP - $this->xpDrank;
            }
            else {
                $xpToAdd = $xp;
            }
            $this->xpDrank = $xpToAdd;
            $this->addToXP($xpToAdd, false);
            return $xp - $xpToAdd;
        }
        else {
            if(($this->maxDrinkableXP - $this->xpDrank) < $xp) {
                $xpToAdd = $this->maxDrinkableXP - $this->xpDrank;
            }
            else {
                $xpToAdd = $xp;
            }
            $this->xpDrank += $xpToAdd;
            $this->addToXP($xpToAdd, false);
            return $xp - $xpToAdd;
        }
    }

    /**
     * @return int
     */
    public function getRestedXP(): int {
        $subtractTime = time() - $this->loadTime;
        $this->restedXP = max(0, ($this->restedXP - $subtractTime));
        return min($this->restedXP, 14400);
    }

    /**
     * @return int
     */
    public function getOnlineTime(): int {
        return $this->onlineTime;
    }

    /**
     * @param int $onlineTime
     */
    public function setOnlineTime(int $onlineTime): void {
        $this->onlineTime = $onlineTime;
    }

    /**
     * @return int
     */
    public function getGuardsKilled(): int {
        return $this->guardsKilled;
    }

    /**
     * @param int $guardsKilled
     */
    public function setGuardsKilled(int $guardsKilled): void {
        $this->guardsKilled = $guardsKilled;
    }

    /**
     * @param int $guardsKilled
     */
    public function addGuardsKilled(int $guardsKilled = 1): void {
        $this->guardsKilled += $guardsKilled;
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public function getQuestProgress(string $name): int {
        return $this->quests[$name];
    }

    /**
     * @return int[]
     */
    public function getQuests(): array {
        return $this->quests;
    }

    public function rerollQuests(): void {
        $quests = $this->core->getGameManager()->getQuestManager()->getQuests();
        shuffle($quests);
        $amount = 6;
        $amount += $this->rank->getExtraQuests();
        $this->quests = [];
        for($i = 0; $i < $amount; $i++) {
            if(empty($quests)) {
                break;
            }
            $quest = array_shift($quests);
            $this->quests[$quest->getName()] = 0;
        }
        $simple = [];
        $uncommon = [];
        $elite = [];
        $ultimate = [];
        $legendary = [];
        $godly = [];
        foreach($this->quests as $name => $progress) {
            $quest = $this->core->getGameManager()->getQuestManager()->getQuest($name);
            switch($quest->getRarity()) {
                case Rarity::SIMPLE:
                    $simple[$name] = $progress;
                    break;
                case Rarity::UNCOMMON:
                    $uncommon[$name] = $progress;
                    break;
                case Rarity::ELITE:
                    $elite[$name] = $progress;
                    break;
                case Rarity::ULTIMATE:
                    $ultimate[$name] = $progress;
                    break;
                case Rarity::LEGENDARY:
                    $legendary[$name] = $progress;
                    break;
                case Rarity::GODLY:
                    $godly[$name] = $progress;
                    break;
            }
        }
        $this->quests = array_merge($simple, $uncommon, $elite, $ultimate, $legendary, $godly);
        $this->lastQuestReroll = time();
    }

    /**
     * @param string $name
     * @param int $amount
     */
    public function setQuestProgress(string $name, int $amount): void {
        $this->quests[$name] = $amount;
    }

    /**
     * @return int
     */
    public function getLastQuestReroll(): int {
        return $this->lastQuestReroll;
    }

    /**
     * @return int
     */
    public function getLotteryBought(): int {
        return $this->lotteryBought;
    }

    /**
     * @param int $lotteryBought
     */
    public function setLotteryBought(int $lotteryBought): void {
        $this->lotteryBought = $lotteryBought;
    }

    /**
     * @return int
     */
    public function getLotteryWon(): int {
        return $this->lotteryWon;
    }

    /**
     * @param int $lotteryWon
     */
    public function setLotteryWon(int $lotteryWon): void {
        $this->lotteryWon = $lotteryWon;
    }

    /**
     * @return int
     */
    public function getLotteryTotalWon(): int {
        return $this->lotteryTotalWon;
    }

    /**
     * @param int $lotteryTotalWon
     */
    public function setLotteryTotalWon(int $lotteryTotalWon): void {
        $this->lotteryTotalWon = $lotteryTotalWon;
    }

    /**
     * @return int
     */
    public function getCoinFlipWon(): int {
        return $this->coinFlipWon;
    }

    /**
     * @param int $coinFlipWon
     */
    public function setCoinFlipWon(int $coinFlipWon): void {
        $this->coinFlipWon = $coinFlipWon;
    }

    /**
     * @return int
     */
    public function getCoinFlipLost(): int {
        return $this->coinFlipLost;
    }

    /**
     * @param int $coinFlipLost
     */
    public function setCoinFlipLost(int $coinFlipLost): void {
        $this->coinFlipLost = $coinFlipLost;
    }

    /**
     * @return int
     */
    public function getCoinFlipTotalWon(): int {
        return $this->coinFlipTotalWon;
    }

    /**
     * @param int $coinFlipTotalWon
     */
    public function setCoinFlipTotalWon(int $coinFlipTotalWon): void {
        $this->coinFlipTotalWon = $coinFlipTotalWon;
    }

    /**
     * @return int
     */
    public function getCoinFlipTotalLost(): int {
        return $this->coinFlipTotalLost;
    }

    /**
     * @param int $coinFlipTotalLost
     */
    public function setCoinFlipTotalLost(int $coinFlipTotalLost): void {
        $this->coinFlipTotalLost = $coinFlipTotalLost;
    }

    /**
     * @return int
     */
    public function getMaxSubtractableXP(): int {
        return XPUtils::getMaxSubtractableXP($this->xp, $this->prestige) - 1;
    }

    public function reset(): void {
        $this->xp = 0;
        $this->prestige = 0;
        $this->maxLevelObtained = 0;
        $this->xpDrank = 0;
        $this->owner->getBossBar()->update($this->getLevelTag(), 0);
//        $this->owner->setNameTag($this->rank->getTagFormatFor($this->owner, [
//            "gangRole" => $this->getGangRoleToString(),
//            "gang" => $this->gang instanceof Gang ? $this->gang->getName() . " " : "",
//            "level" => $this->getPrestigeTag()
//        ]));
        $this->updateNameTag();
        $this->owner->getScoreboard()->setScoreLine(7, "   " . $this->getLevelTag() . TextFormat::GRAY . " (" . number_format((int)$this->xp) . ")");
        $nextLevel = (XPUtils::xpToLevel($this->getXP()) + 1) <= 100 ? (XPUtils::xpToLevel($this->getXP()) + 1) : "prestige";
        $this->owner->getScoreboard()->setScoreLine(9, TextFormat::GRAY . "   " . Utils::shrinkNumber((int)XPUtils::getNeededXP($this->xp, $this->prestige)) . "(" . TextFormat::GREEN . XPUtils::getXPProgress($this->xp, $this->prestige, RPGManager::MODIFIER, 1) . "%%%%" . TextFormat::GRAY . ") to " . TextFormat::WHITE . $nextLevel);
    }

    /**
     * @param NexusPlayer[] $players
     */
    public function updateNameTag(array $players = []): void {
        if(empty($players)) {
            $players = Server::getInstance()->getOnlinePlayers();
        }
        /** @var NexusPlayer $player */
        foreach($players as $player) {
            $color = TextFormat::WHITE;
            if($player->isLoaded() and ($pGang = $player->getDataSession()->getGang()) !== null) {
                if($pGang !== null) {
                    if($this->gang !== null) {
                        $color = $pGang->getRelationColor($this->gang);
                    }
                }
            }
            $tag = $this->rank->getTagFormatFor($this->owner, [
                "gangRole" => $color . $this->getGangRoleToString(),
                "gang" => $this->gang instanceof Gang ? $color . $this->gang->getName() . " " : "",
                "level" => $this->getPrestigeTag()
            ]);
            $this->owner->sendData([$player], [EntityMetadataProperties::NAMETAG => new StringMetadataProperty($tag)]);
        }
    }
}