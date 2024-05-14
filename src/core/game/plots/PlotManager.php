<?php

namespace core\game\plots;

use core\game\plots\command\session\PlotCreateSession;
use core\game\plots\plot\PermissionManager;
use core\game\plots\plot\Plot;
use core\game\plots\plot\PlotOwner;
use core\game\plots\plot\PlotUser;
use core\game\plots\task\PlotExpirationHeartbeatTask;
use core\game\plots\task\UpdatePlotsTask;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\ArrayUtils;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;

class PlotManager {

    /** @var Nexus */
    private $core;

    /** @var Plot[] */
    private $plots = [];

    /** @var PlotCreateSession[] */
    private $plotCreateSessions = [];

    /**
     * PlotManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
        $core->getServer()->getPluginManager()->registerEvents(new PlotListener($this->core), $this->core);
        $core->getScheduler()->scheduleRepeatingTask(new UpdatePlotsTask($this), 6000);
        $core->getScheduler()->scheduleRepeatingTask(new PlotExpirationHeartbeatTask($this), 20);
    }

    public function init(): void {
        $config = new Config($this->core->getDataFolder() . "plots.json", Config::JSON);
        foreach($config->getAll() as $worldName => $plots) {
            $world = $this->core->getServer()->getWorldManager()->getWorldByName((string)$worldName);
            foreach($plots as $plot) {
                $parts = explode(":", $plot);
                $id = (int)$parts[0];
                $p1 = new Position((int)$parts[1], (int)$parts[2], (int)$parts[3], $world);
                $p2 = new Position((int)$parts[4], (int)$parts[5], (int)$parts[6], $world);
                $spawn = new Position((int)$parts[7], (int)$parts[8], (int)$parts[9], $world);
                $this->addPlot(new Plot($id, $p1, $p2, $world, $spawn, 0));
            }
        }
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT id, owner, permissions, expiration FROM plots");
        $stmt->execute();
        $stmt->bind_result($id, $owner, $permissions, $expiration);
        while($stmt->fetch()) {
            if(!isset($this->plots[$id])) {
                throw new PlotException("Unable to find the plot with the id: \"$id\"");
            }
            $userList = ArrayUtils::decodeMultiBoolArray($permissions);
            $users = [];
            foreach($userList as $username => $permissionList) {
                $users[$username] = new PlotUser($username, new PermissionManager($permissionList));
            }
            $owner = new PlotOwner($owner, $users);
            $this->plots[$id]->setOwner($owner);
            $this->plots[$id]->setExpirationTime($expiration);
        }
    }

    /**
     * @param World $world
     *
     * @return bool
     */
    public static function isPlotWorld(World $world): bool {
        if($world->getFolderName() === "citizen" or $world->getFolderName() === "merchant" or $world->getFolderName() === "king") {
            return true;
        }
        return false;
    }

    /**
     * @param Plot $plot
     *
     * @return string
     */
    public static function getPlotColor(Plot $plot): string {
        $world = $plot->getWorld()->getFolderName();
        switch($world) {
            case "citizen":
                return TextFormat::WHITE;
            case "merchant":
                return TextFormat::RED;
            case "king":
                return TextFormat::AQUA;
            default:
                return TextFormat::OBFUSCATED;
        }
    }

    /**
     * @param Plot $plot
     *
     * @return int
     */
    public static function getPlotPrice(Plot $plot): int {
        $world = $plot->getWorld()->getFolderName();
        switch($world) {
            case "citizen":
                return 2500000;
            case "merchant":
                return 100000000;
            case "king":
                return 1000000000;
            default:
                return 0;
        }
    }

    /**
     * @param NexusPlayer $player
     */
    public function addPlotCreateSession(NexusPlayer $player): void {
        $this->plotCreateSessions[$player->getUniqueId()->toString()] = new PlotCreateSession();
    }

    /**
     * @param NexusPlayer $player
     *
     * @return PlotCreateSession|null
     */
    public function getPlotCreateSession(NexusPlayer $player): ?PlotCreateSession {
        return $this->plotCreateSessions[$player->getUniqueId()->toString()] ?? null;
    }

    /**
     * @param NexusPlayer $player
     */
    public function removePlotCreateSession(NexusPlayer $player): void {
        if(isset($this->plotCreateSessions[$player->getUniqueId()->toString()])) {
            unset($this->plotCreateSessions[$player->getUniqueId()->toString()]);
        }
    }

    /**
     * @param Plot $plot
     */
    public function addPlot(Plot $plot): void {
        $this->plots[$plot->getId()] = $plot;
    }

    /**
     * @param Plot $plot
     */
    public function deletePlot(Plot $plot): void {
        unset($this->plots[$plot->getId()]);
    }

    /**
     * @return Plot[]
     */
    public function getPlots(): array {
        return $this->plots;
    }

    /**
     * @param World $world
     *
     * @return Plot[]
     */
    public function getPlotsByWorld(World $world): array {
        $plots = [];
        foreach($this->plots as $plot) {
            if($plot->getWorld()->getFolderName() === $world->getFolderName()) {
                $plots[] = $plot;
            }
        }
        return $plots;
    }

    /**
     * @param int $id
     *
     * @return Plot|null
     */
    public function getPlot(int $id): ?Plot {
        return $this->plots[$id] ?? null;
    }

    /**
     * @param string $player
     *
     * @return Plot|null
     */
    public function getPlotByOwner(string $player): ?Plot {
        foreach($this->plots as $plot) {
            $owner = $plot->getOwner();
            if($owner !== null) {
                if($owner->getUsername() === $player) {
                    return $plot;
                }
            }
        }
        return null;
    }

    /**
     * @param string $player
     *
     * @return Plot[]
     */
    public function getPlotsByUser(string $player): array {
        $plots = [];
        foreach($this->plots as $plot) {
            $owner = $plot->getOwner();
            if($owner !== null) {
                if($owner->getUsername() === $player or $owner->getUser($player) !== null) {
                    $plots[] = $plot;
                }
            }
        }
        return $plots;
    }

    /**
     * @param Position $position
     *
     * @return Plot|null
     */
    public function getPlotInPosition(Position $position): ?Plot {
        $world = $position->getWorld();
        foreach($this->plots as $plot) {
            if($plot->getWorld()->getFolderName() === $world->getFolderName()) {
                if($plot->isPositionInside($position)) {
                    return $plot;
                }
            }
        }
        return null;
    }

    public function savePlots(): void {
        $plots = [];
        foreach($this->plots as $plot) {
            $p1 = $plot->getFirstPosition();
            $p2 = $plot->getSecondPosition();
            $spawn = $plot->getSpawn();
            $plots[$plot->getWorld()->getFolderName()][] = $plot->getId() . ":" . $p1->getX() . ":" . $p1->getY() . ":" . $p1->getZ() . ":" . $p2->getX() . ":" . $p2->getY() . ":" . $p2->getZ() . ":" . $spawn->getX() . ":" . $spawn->getY() . ":" . $spawn->getZ();
        }
        $config =  new Config($this->core->getDataFolder() . "plots.json", Config::JSON);
        foreach($plots as $world => $list) {
            $config->set($world, $list);
        }
        $config->save();
    }
}