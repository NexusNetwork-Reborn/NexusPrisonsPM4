<?php
declare(strict_types=1);

namespace core\player\gang;

use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\ArrayUtils;
use libs\utils\Utils;
use muqsit\invmenu\InvMenu;
use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Gang {

    const RECRUIT = 0;

    const MEMBER = 1;

    const OFFICER = 2;

    const LEADER = 3;

    const MAX_MEMBERS = 15;

    const MAX_ALLIES = 1;

    /** @var string */
    private $name;

    /** @var string[] */
    private $members;

    /** @var string[] */
    private $invites = [];

    /** @var string[] */
    private $allies;

    /** @var string[] */
    private $allyRequests = [];

    /** @var string[] */
    private $enemies = [];

    /** @var int */
    private $value;

    /** @var int */
    private $balance;

    /** @var InvMenu */
    private $vault;

    /** @var PermissionManager */
    private $permissionManager;

    /** @var bool */
    private $needsUpdate = false;

    /**
     * Gang constructor.
     *
     * @param string $name
     * @param array $members
     * @param array $allies
     * @param array $enemies
     * @param int $value
     * @param int $balance
     * @param InvMenu $vault
     * @param array $permissions
     *
     * @throws GangException
     */
    public function __construct(string $name, array $members, array $allies, array $enemies, int $value, int $balance, InvMenu $vault, array $permissions = []) {
        $this->name = $name;
        $this->members = $members;
        $this->allies = $allies;
        $this->enemies = $enemies;
        $this->value = $value;
        $this->balance = $balance;
        $this->vault = $vault;
        $this->permissionManager = new PermissionManager($this, $permissions);
    }

    /**
     * @return string[]
     */
    public function getMembers(): array {
        $this->members = array_unique($this->members);
        return $this->members;
    }

    /**
     * @return NexusPlayer[]
     */
    public function getOnlineMembers(): array {
        $members = [];
        foreach($this->members as $member) {
            $player = Nexus::getInstance()->getServer()->getPlayerByPrefix($member);
            if($player !== null) {
                $members[] = $player;
            }
        }
        return $members;
    }

    /**
     * @param string $player
     *
     * @return bool
     */
    public function isInGang(string $player): bool {
        return in_array($player, $this->getMembers());
    }

    /**
     * @param NexusPlayer $member
     */
    public function addMember(NexusPlayer $member): void {
        $this->members[] = $member->getName();
        $member->getDataSession()->setGang($this);
        $member->getDataSession()->setGangRole(self::RECRUIT);
        $member->getDataSession()->saveDataAsync();
        $this->scheduleUpdate();
    }

    /**
     * @param string $player
     */
    public function removeMember(string $player): void {
        unset($this->members[array_search($player, $this->members)]);
        $p = Server::getInstance()->getPlayerByPrefix($player);
        if($p instanceof NexusPlayer) {
            $p->getDataSession()->setGang(null);
            $p->getDataSession()->setGangRole(null);
            $p->getDataSession()->saveDataAsync();
        }
        $this->scheduleUpdate();
    }

    /**
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public function isInvited(NexusPlayer $player): bool {
        return in_array($player->getName(), $this->invites);
    }

    /**
     * @param NexusPlayer $player
     */
    public function addInvite(NexusPlayer $player): void {
        if(!in_array($player->getName(), $this->invites)) {
            $this->invites[] = $player->getName();
        }
    }

    /**
     * @param NexusPlayer $player
     */
    public function removeInvite(NexusPlayer $player): void {
        unset($this->invites[array_search($player->getName(), $this->invites)]);
    }

    /**
     * @param Gang $gang
     *
     * @return bool
     */
    public function isAllying(Gang $gang): bool {
        return in_array($gang->getName(), $this->allyRequests);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @param Gang $gang
     */
    public function addAllyRequest(Gang $gang): void {
        if(!in_array($gang->getName(), $this->allyRequests)) {
            $this->allyRequests[] = $gang->getName();
        }
    }

    /**
     * @param Gang $gang
     */
    public function addAlly(Gang $gang): void {
        $this->allies[] = $gang->getName();
        $this->removeAllyRequest($gang);
        $this->scheduleUpdate();
    }

    /**
     * @param Gang $gang
     */
    public function removeAllyRequest(Gang $gang): void {
        unset($this->allyRequests[array_search($gang->getName(), $this->allyRequests)]);
    }

    /**
     * @param Gang $gang
     */
    public function removeAlly(Gang $gang): void {
        unset($this->allies[array_search($gang->getName(), $this->allies)]);
        $this->scheduleUpdate();
    }

    /**
     * @return string[]
     */
    public function getAllies(): array {
        $allies = [];
        $this->allies = array_unique($this->allies);
        $manager = Nexus::getInstance()->getPlayerManager()->getGangManager();
        foreach($this->allies as $ally) {
            $ally = $manager->getGang($ally);
            if($ally !== null) {
                if($ally->isAlly($this)) {
                    $allies[] = $ally->getName();
                }
            }
        }
        return $allies;
    }

    /**
     * @param Gang $gang
     *
     * @return bool
     */
    public function isAlly(Gang $gang): bool {
        return in_array($gang->getName(), $this->allies);
    }

    /**
     * @param Gang $gang
     */
    public function addEnemy(Gang $gang): void {
        $this->enemies[] = $gang->getName();
        $this->scheduleUpdate();
    }

    /**
     * @param Gang $gang
     */
    public function removeEnemy(Gang $gang): void {
        unset($this->enemies[array_search($gang->getName(), $this->enemies)]);
        $this->scheduleUpdate();
    }

    /**
     * @return string[]
     */
    public function getEnemies(): array {
        $enemies = [];
        $this->enemies = array_unique($this->enemies);
        $manager = Nexus::getInstance()->getPlayerManager()->getGangManager();
        foreach($this->enemies as $enemy) {
            $enemy = $manager->getGang($enemy);
            if($enemy !== null) {
                if($enemy->isAlly($this)) {
                    $enemy[] = $enemy->getName();
                }
            }
        }
        return $enemies;
    }

    /**
     * @param Gang $gang
     *
     * @return bool
     */
    public function isEnemy(Gang $gang): bool {
        return in_array($gang->getName(), $this->enemies);
    }

    /**
     * @param int $amount
     */
    public function addValue(int $amount): void {
        $this->value += $amount;
        $this->scheduleUpdate();
    }

    /**
     * @param int $amount
     */
    public function subtractValue(int $amount): void {
        $this->value -= $amount;
        $this->scheduleUpdate();
    }

    /**
     * @return int
     */
    public function getValue(): int {
        return $this->value;
    }

    /**
     * @param int $amount
     */
    public function addMoney(int $amount): void {
        $this->balance += $amount;
        $this->scheduleUpdate();
    }

    /**
     * @param int $amount
     */
    public function subtractMoney(int $amount): void {
        $this->balance -= $amount;
        $this->scheduleUpdate();
    }

    /**
     * @return int
     */
    public function getBalance(): int {
        return $this->balance;
    }

    /**
     * @param NexusPlayer $player
     */
    public function sendVault(NexusPlayer $player): void {
        $this->vault->send($player);
    }

    /**
     * @return mixed
     */
    public function getPermissionManager() {
        return $this->permissionManager;
    }

    /**
     * @param Gang $gang
     *
     * @return string
     */
    public function getRelationColor(Gang $gang): string {
        if($this->isAlly($gang)) {
            return TextFormat::AQUA;
        }
        if($this->isEnemy($gang)) {
            return TextFormat::RED;
        }
        if($gang->getName() === $this->getName()) {
            return TextFormat::GREEN;
        }
        return TextFormat::WHITE;
    }

    public function scheduleUpdate(): void {
        $this->needsUpdate = true;
    }

    /**
     * @return bool
     */
    public function needsUpdate(): bool {
        return $this->needsUpdate;
    }

    public function disband(): void {
        foreach($this->getOnlineMembers() as $member) {
            if($member->isLoaded()) {
                $member->getDataSession()->setGang(null);
                $member->getDataSession()->setGangRole(null);
            }
        }
        $database = Nexus::getInstance()->getMySQLProvider()->getDatabase();
        $null = null;
        $stmt = $database->prepare("UPDATE stats SET gang = ?, gangRole = ? WHERE gang = ?");
        $stmt->bind_param("sss", $null, $null, $name);
        $stmt->execute();
        $stmt->close();
        $manager = Nexus::getInstance()->getPlayerManager()->getGangManager();
        foreach($this->allies as $ally) {
            $ally = $manager->getGang($ally);
            if($ally !== null and $ally->isAlly($this)) {
                $ally->removeAlly($this);
            }
        }
        foreach($manager->getGangs() as $gang) {
            if($gang->isEnemy($this)) {
                $gang->removeEnemy($this);
            }
        }
        $manager->removeGang($this->name);
    }

    public function updateAsync(): void {
        if($this->needsUpdate) {
            $this->needsUpdate = false;
            $members = implode(",", $this->members);
            $allyList = implode(",", $this->allies);
            $enemyList = implode(",", $this->enemies);
            $permissions = ArrayUtils::encodeArray($this->permissionManager->getPermissions());
            $inv = Nexus::encodeInventory($this->vault->getInventory());
            if($this->name === "Hentai") {
                $items = Nexus::decodeInventory($inv);
                /** @var Item $item */
                foreach($items as $item) {
                    var_dump($item->getEnchantments());
                }
            }
            $connector = Nexus::getInstance()->getMySQLProvider()->getConnector();
            $connector->executeUpdate("REPLACE INTO gangs(name, members, allies, enemies, value, balance, permissions, vault) VALUES(?, ?, ?, ?, ?, ?, ?, ?)", "ssssiiss", [
                $this->name,
                $members,
                $allyList,
                $enemyList,
                $this->value,
                $this->balance,
                $permissions,
                $inv
            ]);
        }
    }

    public function update(): void {
        if($this->needsUpdate) {
            $database = Nexus::getInstance()->getMySQLProvider()->getDatabase();
            $this->needsUpdate = false;
            $members = implode(",", $this->members);
            $allyList = implode(",", $this->allies);
            $enemyList = implode(",", $this->enemies);
            $permissions = ArrayUtils::encodeArray($this->permissionManager->getPermissions());
            $inv = Nexus::encodeInventory($this->vault->getInventory());
            if($this->name === "Hentai") {
                $items = Nexus::decodeInventory($inv);
                /** @var Item $item */
                foreach($items as $item) {
                    var_dump($item->getEnchantments());
                }
            }
            $stmt = $database->prepare("REPLACE INTO gangs(name, members, allies, enemies, value, balance, permissions, vault) VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiiss",  $this->name, $members, $allyList, $enemyList, $this->value, $this->balance, $permissions, $inv);
            $stmt->execute();
            $stmt->close();
        }
    }
}