<?php

declare(strict_types = 1);

namespace core\player\task;

use core\game\combat\CombatManager;
use core\level\LevelManager;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Task;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class ExecutiveMineHeartbeatTask extends Task {

    /** @var Nexus */
    private $core;

    private $clusterFrequency = 0;

    private $clusterSeconds = 0;

    /** @var array<string,[string, Position]> */
    public static $floatingTexts = [];

    private static $blockToID = [];

    /**
     * UpdateTask constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->clusterFrequency = LevelManager::getSetup()->getNested("executive-mine.building-block-frequency");
    }

    public function onRun(): void {
        if(date_default_timezone_get() !== "America/Los_Angeles") {
            date_default_timezone_set("America/Los_Angeles");
        }
        $this->clusterSeconds += 30;
        if($this->clusterSeconds >= $this->clusterFrequency) {
            $this->clusterSeconds = 0;
            $this->generateCluster();
        }
        $time = explode(":", date("h:i:A", time()));
        $hour = (int) $time[0];
        $minute = (int) $time[1];
        $type = $time[2];
        if($hour == 12 && $minute == 0 && $type == "PM") {
            /** @var NexusPlayer $player */
            foreach($this->core->getServer()->getOnlinePlayers() as $player) {
                if($player->isLoaded()) {
                    $player->getDataSession()?->resetExecutiveMineTime();
                }
            }
            $this->core->getMySQLProvider()->getDatabase()->query("UPDATE executive SET duration = 600;");
            $this->core->getServer()->broadcastMessage(TextFormat::BOLD . TextFormat::AQUA . "(!) " . TextFormat::RESET . TextFormat::YELLOW . "The Executive Mine time has been reset for everyone!");
        }
    }

    public function generateCluster() : void {
        $clusters = LevelManager::getSetup()->getNested("executive-mine.building-block-clusters");
        $world = $this->core->getServer()->getWorldManager()->getWorldByName("executive");
        $cluster = $clusters[array_rand($clusters)];
        $positions = [];
        foreach($cluster as $position) {
            $positions[] = LevelManager::stringToPosition($position, $world);
        }
        $pos = array_shift($positions);
        foreach ($positions as $pos) {
            if($world->getBlock($pos)->getId() !== BlockLegacyIds::AIR) {
                return;
            }
        }
        $id = uniqid("", true);
        foreach($positions as $pos) {
            self::$blockToID[$pos->getX() . ":" . $pos->getY() . ":" . $pos->getZ()] = $id;
            $world->setBlock($pos, VanillaBlocks::END_STONE());
        }
        //self::$floatingTexts[$id] = [TextFormat::YELLOW . TextFormat::BOLD . "Building Blocks\n" . TextFormat::RESET . "When mined these blocks can be placed\nfor a limited time, to access deeper locations in the mine,\nwhere you can find more " . TextFormat::BLUE . "Prismarine" . TextFormat::RESET . " and the " . TextFormat::DARK_RED  . TextFormat::BOLD . "Executive Wormhole!", $pos];
//        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
//            if($player instanceof NexusPlayer) {
//                //$player->addFloatingText($pos, $id, self::$floatingTexts[$id][0]);
//            }
//        }
    }

    public static function removeFloatingText(Position $pos) : void {
        $key = $pos->getX() . ":" . $pos->getY() . ":" . $pos->getZ();
        if(isset(self::$blockToID[$key])) {
            //$id = self::$blockToID[$key];
            unset(self::$blockToID[$key]);

//            if(!in_array($id, self::$blockToID)) {
//                //unset(self::$floatingTexts[$id]);
//                /** @var NexusPlayer $player */
//                foreach (Server::getInstance()->getOnlinePlayers() as $player) {
//                    //$player->removeFloatingText($id);
//                }
//            }
        }
    }
}