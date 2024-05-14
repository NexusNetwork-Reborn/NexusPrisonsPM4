<?php
declare(strict_types=1);

namespace core\provider;

use core\Nexus;
use core\provider\task\ReadResultsTask;
use core\provider\thread\MySQLThread;
use mysqli;
use pocketmine\utils\Config;

class MySQLProvider {

    /** @var Nexus */
    private $core;

    /** @var mysqli */
    private $database;

    /** @var MySQLCredentials */
    private $credentials;

    /** @var string */
    private $schema;

    /** @var MySQLThread */
    private $thread;

    /**
     * MySQLProvider constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $cfg = new Config($this->core->getDataFolder() . "setup.yml", Config::YAML);
        $this->schema = $cfg->getNested("db.schema");
        $this->database = new mysqli($cfg->getNested("db.hostname"), $cfg->getNested("db.username"), $cfg->getNested("db.password"), $this->schema, 3306);
        $this->credentials = new MySQLCredentials($cfg->getNested("db.hostname"), $cfg->getNested("db.username"), $cfg->getNested("db.password"), $this->schema);
        $this->init();
        $this->thread = new MySQLThread($this->credentials);
        $this->thread->start(PTHREADS_INHERIT_INI | PTHREADS_INHERIT_CONSTANTS);
        $core->getScheduler()->scheduleRepeatingTask(new ReadResultsTask($this->thread), 1);
    }

    public function init(): void {
        $this->database->query("CREATE TABLE IF NOT EXISTS players(
            uuid VARCHAR(36) PRIMARY KEY, 
            username VARCHAR(16), 
            rankId TINYINT DEFAULT 0, 
            permissions VARCHAR(600) DEFAULT '', 
            permanentPermissions VARCHAR(600) DEFAULT '', 
            votePoints BIGINT DEFAULT 0, 
            tags BLOB DEFAULT '', 
            currentTag VARCHAR(150) DEFAULT '', 
            vaults INT DEFAULT 0,
            homes INT DEFAULT 0,
            showcases INT DEFAULT 1,
            showcase BLOB DEFAULT NULL,
            inbox BLOB DEFAULT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS stats(
            uuid VARCHAR(36) PRIMARY KEY, 
            username VARCHAR(16), 
            gang VARCHAR(30) DEFAULT NULL, 
            gangRole TINYINT DEFAULT NULL, 
            xp BIGINT DEFAULT 0, 
            prestige TINYINT DEFAULT 0, 
            balance DOUBLE DEFAULT 0, 
            blocks BIGINT DEFAULT 0, 
            bankBlock BIGINT DEFAULT 0, 
            kills SMALLINT DEFAULT 0, 
            onlineTime BIGINT DEFAULT 0,
            kitCooldowns VARCHAR(4000) DEFAULT '', 
            kitTiers VARCHAR(4000) DEFAULT '', 
            maxLevelObtained SMALLINT DEFAULT 0,
            drinkXPTime BIGINT DEFAULT 0,
            xpDrank BIGINT DEFAULT 0,
            maxDrinkableXP BIGINT DEFAULT 0,
            lastOnlineTime BIGINT DEFAULT 0,
            restedXP SMALLINT DEFAULT 14400,
            quests VARCHAR(4000) DEFAULT '', 
            lastQuestReroll BIGINT DEFAULT 0
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS gamblingStats(
            uuid VARCHAR(36) PRIMARY KEY, 
            username VARCHAR(16), 
            lotteryBought BIGINT DEFAULT 0, 
            lotteryWon BIGINT DEFAULT 0, 
            lotteryTotalWon BIGINT DEFAULT 0, 
            coinFlipWon BIGINT DEFAULT 0, 
            coinFlipLost BIGINT DEFAULT 0, 
            coinFlipTotalWon BIGINT DEFAULT 0, 
            coinFlipTotalLost BIGINT DEFAULT 0
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS modifiers(
            uuid VARCHAR(36) PRIMARY KEY, 
            username VARCHAR(16),  
            energyModifier FLOAT DEFAULT 1.0,
            energyTime BIGINT DEFAULT 0,
            energyDuration BIGINT DEFAULT 0,
            xpModifier FLOAT DEFAULT 1.0,
            xpTime BIGINT DEFAULT 0,
            xpDuration BIGINT DEFAULT 0,
            executiveTime BIGINT DEFAULT 0,
            executiveDuration BIGINT DEFAULT 0
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS ipAddress(
            uuid VARCHAR(36), 
            username VARCHAR(16), 
            ipAddress VARCHAR(20), 
            riskLevel TINYINT NOT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS gangs(
            name VARCHAR(30) NOT NULL PRIMARY KEY, 
            members TEXT NOT NULL, 
            allies TEXT DEFAULT NULL, 
            enemies TEXT DEFAULT NULL,
            balance BIGINT DEFAULT 0 NOT NULL, 
            value BIGINT DEFAULT 0 NOT NULL, 
            permissions VARCHAR(4000) DEFAULT '',
            vault BLOB DEFAULT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS vaults(
            uuid INT PRIMARY KEY AUTO_INCREMENT, 
            username VARCHAR(16) NOT NULL, 
            id SMALLINT, 
            items BLOB DEFAULT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS homes(
            uuid VARCHAR(36) NOT NULL, 
            username VARCHAR(16), 
            name VARCHAR(16) NOT NULL,
            x SMALLINT NOT NULL, 
            y SMALLINT NOT NULL, 
            z SMALLINT NOT NULL, 
            level VARCHAR(30) NOT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS plots(
            id SMALLINT NOT NULL PRIMARY KEY, 
            owner VARCHAR(16) NOT NULL,
            permissions VARCHAR(4000) DEFAULT '',
            expiration BIGINT DEFAULT 0
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS auctions(
            identifier INT PRIMARY KEY, 
            seller VARCHAR(16) NOT NULL, 
            item BLOB NOT NULL, 
            startTime BIGINT NOT NULL,
            buyPrice BIGINT DEFAULT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS blackAuctionHistory(
            soldTime BIGINT PRIMARY KEY, 
            buyer VARCHAR(16) NOT NULL, 
            item BLOB NOT NULL,
            buyPrice BIGINT DEFAULT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS punishments(
            username VARCHAR(16) PRIMARY KEY, 
            type TINYINT, 
            expiration BIGINT DEFAULT 0, 
            time BIGINT, 
            effector VARCHAR(16), 
            reason VARCHAR(100)
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS punishmentHistory(
            username VARCHAR(16), 
            type TINYINT, 
            expiration BIGINT DEFAULT 0, 
            time BIGINT, 
            effector VARCHAR(16), 
            reason VARCHAR(100)
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS executive(
            uuid VARCHAR(36) PRIMARY KEY, 
            duration BIGINT DEFAULT 600,
            thresholdBonus BIGINT DEFAULT 0
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS itemSkins(
            uuid VARCHAR(36) PRIMARY KEY,
            skins BLOB DEFAULT '',
            currentSkin VARCHAR(150) DEFAULT ''
        );");
    }

    public function reset(): void {
        $this->database->query("UPDATE players SET gang = NULL, gangRole = NULL, balance = 0, bounty = 0, permissions = '', kills = 0, luckyBlocks = 0;");
        $this->database->query("DROP TABLE nexusPass, gangs, claims, inboxes, crates, kitCooldowns, homes, auctions, auctionInboxes;");
    }

    public function realReset() : void {
        $this->database->query("DROP TABLE players, stats, gamblingStats, modifiers, ipAddress, gangs, vaults, homes, plots, auctions, punishments, punishmentHistory, executive, itemSkins;");
    }

    /**
     * @return MySQLThread
     */
    public function createNewThread(): MySQLThread {
        if(!$this->thread->isRunning()) {
            $this->thread = new MySQLThread($this->credentials);
            $this->thread->start(PTHREADS_INHERIT_INI | PTHREADS_INHERIT_CONSTANTS);
        }
        return $this->thread;
    }

    /**
     * @return string
     */
    public function getMainDatabaseName(): string {
        return $this->credentials->getDatabase();
    }

    /**
     * @return mysqli
     */
    public function getDatabase(): mysqli {
        return $this->database;
    }

    /**
     * @return MySQLThread
     */
    public function getConnector(): MySQLThread {
        return $this->thread;
    }

    /**
     * @return MySQLCredentials
     */
    public function getCredentials(): MySQLCredentials {
        return $this->credentials;
    }
}