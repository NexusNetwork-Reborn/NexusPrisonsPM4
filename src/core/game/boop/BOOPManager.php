<?php

declare(strict_types = 1);

namespace core\game\boop;

use core\Nexus;
use core\game\boop\handler\HandlerManager;
use core\game\boop\task\PunishmentLogTask;
use pocketmine\Server;

class BOOPManager {

    /** @var Nexus */
    private $core;

    /** @var PunishmentEntry[][] */
    private $entries = [];

    /** @var PunishmentEntry[][][][] */
    private $history = [];

    /** @var HandlerManager */
    private $handlerManager;

    /**
     * BOOPManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new BOOPListener($core), $core);
        $this->handlerManager = new HandlerManager($core);
        $this->init();
    }

    public function init(): void {
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT username, type, expiration, time, effector, reason FROM punishments");
        $stmt->execute();
        $stmt->bind_result($username, $type, $expiration, $time, $effector, $reason);
        while($stmt->fetch()) {
            $this->entries[$type][$username] = new PunishmentEntry($username, $type, $expiration, $time, $effector, $reason);
        }
        $stmt->close();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT username, type, expiration, time, effector, reason FROM punishmentHistory");
        $stmt->execute();
        $stmt->bind_result($username, $type, $expiration, $time, $effector, $reason);
        while($stmt->fetch()) {
            $this->history[$username][$type][$reason][] = new PunishmentEntry($username, $type, $expiration, $time, $effector, $reason);
        }
        $stmt->close();
    }

    /**
     * @param string $username
     * @param int $type
     * @param string $effector
     * @param string $reason
     * @param int|null $expiration
     *
     * @return PunishmentEntry
     *
     * @throws BOOPException
     */
    public function punish(string $username, int $type, string $effector, string $reason, ?int $expiration = null): PunishmentEntry {
        $username = strtolower($username);
        if($expiration === null) {
            $violations = 0;
            if(isset($this->history[$username][$type][$reason])) {
                $violations = count($this->history[$username][$type][$reason]);
            }
            $expiration = $this->getExpirationForViolations($violations, $reason);
        }

        if($type === PunishmentEntry::BAN) {
            $typeString = "Ban";
        }
        elseif($type  === PunishmentEntry::MUTE) {
            $typeString = "Mute";
        }
        elseif($type  === PunishmentEntry::BLOCK) {
            $typeString = "Block";
        }
        else {
            $typeString = "Unknown";
        }
        Server::getInstance()->getAsyncPool()->submitTask(new PunishmentLogTask("Player: $username\nServer: Prison\nPunisher: $effector\nPunishment: $typeString\nReason: $reason\n"));
        $time = time();
        $entry = new PunishmentEntry($username, $type, $expiration, $time, $effector, $reason);
        $this->entries[$type][$username] = $entry;
        $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO punishments(username, type, expiration, time, effector, reason) VALUES(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiiss", $username, $type, $expiration, $time, $effector, $reason);
        $stmt->execute();
        $stmt->close();
        return $entry;
    }

    /**
     * @param PunishmentEntry $entry
     * @param string|null $reliever
     */
    public function relieve(PunishmentEntry $entry, ?string $reliever = null): void {
        $reliever = $reliever !== null ? $reliever : "Unknown";
        $type = $entry->getType();
        $username = $entry->getUsername();
        $expiration = $entry->getExpiration();
        $time = $entry->getTime();
        $effector = $entry->getEffector();
        $reason = $entry->getReason();
        if(isset($this->entries[$type][$username])) {
            unset($this->entries[$type][$username]);
        }
        if($type === PunishmentEntry::BAN) {
            $typeString = "Ban";
        }
        elseif($type  === PunishmentEntry::MUTE) {
            $typeString = "Mute";
        }
        elseif($type  === PunishmentEntry::BLOCK) {
            $typeString = "Block";
        }
        else {
            $typeString = "Unknown";
        }
        Server::getInstance()->getAsyncPool()->submitTask(new PunishmentLogTask("Player: $username\nServer: Prison\nReliever: $reliever\nRevilement: $typeString\n"));
        $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("DELETE FROM punishments WHERE username = ? AND type = ?");
        $stmt->bind_param("si", $username, $type);
        $stmt->execute();
        $stmt->close();
        $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO punishmentHistory(username, type, expiration, time, effector, reason) VALUES(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiiss", $username, $type, $expiration, $time, $effector, $reason);
        $stmt->execute();
        $stmt->close();
        $this->history[$username][$type][$reason][] = $entry;
    }

    /**
     * @param string $username
     *
     * @return bool
     */
    public function isMuted(string $username): bool {
        $username = strtolower($username);
        if(!isset($this->entries[PunishmentEntry::MUTE][$username])) {
            return false;
        }
        if(!$this->entries[PunishmentEntry::MUTE][$username]->check()) {
            $this->relieve($this->entries[PunishmentEntry::MUTE][$username]);
            return false;
        }
        return true;
    }

    /**
     * @param string $username
     *
     * @return bool
     */
    public function isBanned(string $username): bool {
        $username = strtolower($username);
        if(!isset($this->entries[PunishmentEntry::BAN][$username])) {
            return false;
        }
        if(!$this->entries[PunishmentEntry::BAN][$username]->check()) {
            $this->relieve($this->entries[PunishmentEntry::BAN][$username]);
            return false;
        }
        return true;
    }

    /**
     * @param string $username
     *
     * @return bool
     */
    public function isBlocked(string $username): bool {
        $username = strtolower($username);
        if(!isset($this->entries[PunishmentEntry::BLOCK][$username])) {
            return false;
        }
        if(!$this->entries[PunishmentEntry::BLOCK][$username]->check()) {
            $this->relieve($this->entries[PunishmentEntry::BLOCK][$username]);
            return false;
        }
        return true;
    }

    /**
     * @param string $username
     *
     * @return PunishmentEntry|null
     */
    public function getBan(string $username): ?PunishmentEntry {
        return $this->entries[PunishmentEntry::BAN][strtolower($username)] ?? null;
    }

    /**
     * @return PunishmentEntry[]
     */
    public function getBans(): array {
        return $this->entries[PunishmentEntry::BAN] ?? [];
    }

    /**
     * @param string $username
     *
     * @return PunishmentEntry|null
     */
    public function getMute(string $username): ?PunishmentEntry {
        return $this->entries[PunishmentEntry::MUTE][strtolower($username)] ?? null;
    }

    /**
     * @return PunishmentEntry[]
     */
    public function getMutes(): array {
        return $this->entries[PunishmentEntry::MUTE] ?? [];
    }

    /**
     * @param string $username
     *
     * @return PunishmentEntry|null
     */
    public function getBlock(string $username): ?PunishmentEntry {
        return $this->entries[PunishmentEntry::BLOCK][strtolower($username)] ?? null;
    }

    /**
     * @return PunishmentEntry[]
     */
    public function getBlocks(): array {
        return $this->entries[PunishmentEntry::BLOCK] ?? [];
    }

    /**
     * @param string $username
     *
     * @return PunishmentEntry[][][]
     */
    public function getHistoryOf(string $username): array {
        $username = strtolower($username);
        return $this->history[$username] ?? [];
    }

    /**
     * @param int $violations
     * @param string $reason
     *
     * @return int
     *
     * @throws BOOPException
     */
    public function getExpirationForViolations(int $violations, string $reason): int {
        switch($reason) {
            case Reasons::HACK:
                if($violations === 0) {
                    return 604800;
                }
                elseif($violations === 1) {
                    return 2592000;
                }
                else {
                    return 0;
                }
                break;
            case Reasons::ADVERTISING:
            case Reasons::DDOS_THREATS:
            case Reasons::BAN_EVADING:
            case Reasons::ALTING:
            case Reasons::IRL_SCAMMING:
                return 0;
                break;
            case Reasons::EXPLOITING:
                if($violations === 0) {
                    return 259200;
                }
                elseif($violations === 1) {
                    return 604800;
                }
                elseif($violations === 2) {
                    return 2592000;
                }
                else {
                    return 0;
                }
                break;
            case Reasons::SPAMMING:
            case Reasons::RACIAL_SLURS:
            case Reasons::STAFF_DISRESPECT:
                if($violations === 0) {
                    return 900;
                }
                elseif($violations === 1) {
                    return 3600;
                }
                else {
                    return 10800;
                }
                break;
            default:
                throw new BOOPException("Invalid reason: $reason");
        }
    }

    /**
     * @return HandlerManager
     */
    public function getHandlerManager(): HandlerManager {
        return $this->handlerManager;
    }
}
