<?php

declare(strict_types = 1);

namespace core\game\boop\task;

use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class UploadIPTask extends Task {

    /** @var string */
    private $player;

    /** @var string */
    private $ip;

    /** @var int */
    private $riskLevel;

    /**
     * UploadIPTask constructor.
     *
     * @param string $player
     * @param string $ip
     * @param int $riskLevel
     */
    public function __construct(string $player, string $ip, int $riskLevel) {
        $this->player = $player;
        $this->ip = $ip;
        $this->riskLevel = $riskLevel;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        $server = Server::getInstance();
        $player = $server->getPlayerByPrefix($this->player);
        if($player === null) {
            return;
        }
        switch($this->riskLevel) {
            case 0:
                $server->getLogger()->info("No malicious ip swapper was detected in {$this->player}.");
                $uuid = $player->getUniqueId()->toString();
                $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO ipAddress(uuid, username, ipAddress, riskLevel) VALUES(?, ?, ?, ?)");
                $stmt->bind_param("sssi", $uuid, $this->player, $this->ip, $this->riskLevel);
                $stmt->execute();
                $stmt->close();
                break;
            case 1:
                $server->getLogger()->warning("A malicious ip swapper was detected in {$this->player}.");
                $uuid = $player->getUniqueId()->toString();
                $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO ipAddress(uuid, username, ipAddress, riskLevel) VALUES(?, ?, ?, ?)");
                $stmt->bind_param("sssi", $uuid, $this->player, $this->ip, $this->riskLevel);
                $stmt->execute();
                $stmt->close();
                if(!$player instanceof NexusPlayer) {
                    return;
                }
                //$player->kickDelay(TextFormat::RED . "A malicious ip swapper was detected!");
                break;
            case 2:
                $uuid = $player->getUniqueId()->toString();
                $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO ipAddress(uuid, username, ipAddress, riskLevel) VALUES(?, ?, ?, ?)");
                $stmt->bind_param("sssi", $uuid, $this->player, $this->ip, $this->riskLevel);
                $stmt->execute();
                $stmt->close();
                $server->getLogger()->info("No malicious ip swapper was detected in {$this->player} but could potentially be using one.");
                break;
            default:
                $server->getLogger()->warning("Error in checking {$this->player}'s proxy.");
                if(!$player instanceof NexusPlayer) {
                    return;
                }
                $player->kickDelay(TextFormat::RED . "An ip check was conducted and had failed. Please rejoin to complete this check.");
        }
    }

    public function onCancel(): void {
        $server = Server::getInstance();
        $player = $server->getPlayerByPrefix($this->player);
        switch($this->riskLevel) {
            case 0:
                $server->getLogger()->info("No malicious ip swapper was detected in {$this->player}.");
                $uuid = $player->getUniqueId()->toString();
                $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO ipAddress(uuid, username, ipAddress, riskLevel) VALUES(?, ?, ?, ?)");
                $stmt->bind_param("sssi", $uuid, $this->player, $this->ip, $this->riskLevel);
                $stmt->execute();
                $stmt->close();
                break;
            case 1:
                $server->getLogger()->warning("A malicious ip swapper was detected in {$this->player}.");
                $uuid = $player->getUniqueId()->toString();
                $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO ipAddress(uuid, username, ipAddress, riskLevel) VALUES(?, ?, ?, ?)");
                $stmt->bind_param("sssi", $uuid, $this->player, $this->ip, $this->riskLevel);
                $stmt->execute();
                $stmt->close();
                if(!$player instanceof NexusPlayer) {
                    return;
                }
                //$player->kickDelay(TextFormat::RED . "A malicious ip swapper was detected!");
                break;
            case 2:
                $uuid = $player->getUniqueId()->toString();
                $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO ipAddress(uuid, username, ipAddress, riskLevel) VALUES(?, ?, ?, ?)");
                $stmt->bind_param("sssi", $uuid, $this->player, $this->ip, $this->riskLevel);
                $stmt->execute();
                $stmt->close();
                $server->getLogger()->info("No malicious ip swapper was detected in {$this->player} but could potentially be using one.");
                break;
            default:
                $server->getLogger()->warning("Error in checking {$this->player}'s proxy.");
                if(!$player instanceof NexusPlayer) {
                    return;
                }
                $player->kickDelay(TextFormat::RED . "An ip check was conducted and had failed. Please rejoin to complete this check.");
        }
    }
}
