<?php

namespace core\game\plots\plot;

use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\ArrayUtils;
use libs\utils\Utils;
use pocketmine\Server;

class PlotOwner {

    /** @var string */
    private $username;

    /** @var PlotUser[] */
    private $users;

    /** @var Plot */
    private $plot;

    /** @var bool */
    private $needsUpdate = false;

    /**
     * PlotOwner constructor.
     *
     * @param string $username
     * @param PlotUser[] $users
     */
    public function __construct(string $username, array $users) {
        $this->username = $username;
        $this->users = $users;
    }

    /**
     * @return string
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * @return NexusPlayer[]
     */
    public function getOnlineUsers(): array {
        $users = [];
        $server = Server::getInstance();
        $users[] = $server->getPlayerByPrefix($this->username);
        foreach($this->users as $user) {
            $users[] = $server->getPlayerByPrefix($user->getUsername());
        }
        return array_filter($users);
    }

    /**
     * @param string $username
     *
     * @return PlotUser|null
     */
    public function getUser(string $username): ?PlotUser {
        return $this->users[$username] ?? null;
    }

    /**
     * @return PlotUser[]
     */
    public function getUsers(): array {
        return $this->users;
    }

    /**
     * @param NexusPlayer $player
     */
    public function addUser(NexusPlayer $player): void {
        $this->users[$player->getName()] = new PlotUser($player->getName(), new PermissionManager([]));
        $this->scheduleUpdate();
    }

    /**
     * @param string $username
     */
    public function removeUser(string $username): void {
        unset($this->users[$username]);
        $this->scheduleUpdate();
    }

    /**
     * @return Plot
     */
    public function getPlot(): Plot {
        return $this->plot;
    }

    /**
     * @param Plot $plot
     */
    public function setPlot(Plot $plot): void {
        $this->plot = $plot;
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

    public function updateAsync(): void {
        if($this->needsUpdate) {
            $this->needsUpdate = false;
            $users = [];
            foreach($this->users as $plotUser) {
                $users[$plotUser->getUsername()] = $plotUser->getPermissionManager()->getPermissions();
            }
            $permissions = ArrayUtils::encodeMultiBoolArray($users);
            $connector = Nexus::getInstance()->getMySQLProvider()->getConnector();
            $connector->executeUpdate("REPLACE INTO plots(id, owner, permissions, expiration) VALUES(?, ?, ?, ?)", "issi", [
                $this->plot->getId(),
                $this->plot->getOwner()->getUsername(),
                $permissions,
                $this->plot->getExpirationTime()
            ]);
        }
    }

    public function update(): void {
        if($this->needsUpdate) {
            $database = Nexus::getInstance()->getMySQLProvider()->getDatabase();
            $this->needsUpdate = false;
            $users = [];
            foreach($this->users as $plotUser) {
                $users[$plotUser->getUsername()] = $plotUser->getPermissionManager()->getPermissions();
            }
            $permissions = ArrayUtils::encodeMultiBoolArray($users);
            $id = $this->plot->getId();
            $owner = $this->plot->getOwner()->getUsername();
            $expiration = $this->plot->getExpirationTime();
            $stmt = $database->prepare("REPLACE INTO plots(id, owner, permissions, expiration) VALUES(?, ?, ?, ?)");
            $stmt->bind_param("issi",  $id, $owner, $permissions, $expiration);
            $stmt->execute();
            $stmt->close();
        }
    }
}