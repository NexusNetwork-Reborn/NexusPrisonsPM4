<?php

namespace core\level\task;

use core\level\block\Ore;
use core\level\LevelManager;
use core\level\tile\RushOreBlock;
use core\Nexus;
use core\player\NexusPlayer;
use customiesdevs\customies\block\CustomiesBlockFactory;
use libs\utils\FloatingTextParticle;
use libs\utils\Task;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\item\ItemIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

class SpawnRushOreTask extends Task {

    private static $instance;

    /** @var LevelManager */
    private $manager;

    /** @var World */
    private $world;

    /** @var int */
    private $spawnRate = 600;

    /**
     * SpawnMeteoriteTask constructor.
     *
     * @param LevelManager $manager
     */
    public function __construct(LevelManager $manager) {
        self::$instance = $this;
        $this->manager = $manager;
        $this->world = Server::getInstance()->getWorldManager()->getDefaultWorld();
    }

    public function onRun(): void {
        if(count(Server::getInstance()->getOnlinePlayers()) < 2) {
            return;
        }
//        if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_THREE)) {
//            return;
//        }
        if(Nexus::getInstance()->getServerManager()->getAnnouncementManager()->getRestarter()->getRestartProgress() > 5) {
            if(--$this->spawnRate <= 0) {
                $this->spawnOreRush();
                $this->spawnRate = 600;
            }
        }
    }

    public function spawnOreRush() : bool {
        $options = [];
        foreach (LevelManager::getSetup()->getNested("ore-rush") as $key => $ore) {
            $xz = explode(":", $ore["xz"]);
            $xzPos = new Position((float)$xz[0], 0, (float)$xz[1], $this->world);
            $xz2 = explode(":", $ore["xz2"]);
            $xz2Pos = new Position((float)$xz2[0], 255, (float)$xz2[1], $this->world);

            $axis = new AxisAlignedBB($xzPos->getX(), $xzPos->getY(), $xzPos->getZ(), $xz2Pos->getX(), $xz2Pos->getY(), $xz2Pos->getZ());
            $ents = $this->world->getNearbyEntities($axis);
            $players = [];
            foreach ($ents as $ent) {
                if ($ent instanceof NexusPlayer) {
                    $players[] = $ent;
                }
            }

            if(count($players) > 1) {
                $options[] = [$axis, $key];
            }
        }
        if(count($options) > 0) {
            $option = $options[mt_rand(0, count($options) - 1)];
            $axis = $option[0];
            $pos = $this->getRandomPosition($axis);
            $key = $option[1];
            if($pos !== null) {
                $chunk = $this->world->getOrLoadChunkAtPosition($pos);
                if($chunk !== null && $this->world->getTile($pos) === null) {
                    foreach($chunk->getTiles() as $tile) {
                        if($tile instanceof RushOreBlock) {
                            return false;
                        }
                    }
                    //TODO: Make floating text here
                    $this->world->setBlock($pos, BlockFactory::getInstance()->get(self::keyToID($key), 0));
                    $tile = $this->world->getTile($pos);
                    $animationID = self::keyToAnimationID($key);
                    $id = serialize($pos->asVector3());
                    $pos->y += 3;
                    self::$fancyFloaties[$id] = new FloatingTextParticle($pos, $id, $tile->getInfoByID(self::keyToID($key)), Nexus::getInstance()->getDisplayManager()->getAnimationManager()->getSkin($animationID));
                    foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                        if($player instanceof NexusPlayer) {
                            $player->floatingTexts[$id] = self::$fancyFloaties[$id];
                            $player->floatingTexts[$id]->sendChangesTo($player);
                        }
                    }
                    self::$rushes[] = $pos;
                    //$tile = new RushOreBlock($this->world, $pos);
                    //$this->world->addTile($tile);
                    //$tile = $this->world->getTile($pos);
                    //if($tile instanceof RushOreBlock) {
                        Nexus::getInstance()->getServer()->broadcastMessage($tile->getBaseInfo(self::keyToID($key)) . " has spawned at {$pos->getX()}, {$pos->getY()}, {$pos->getZ()}");
                   // }
                    return true;
                }
            }
        }
        return false;
    }

    public static $fancyFloaties = [];
    public static $rushes = [];

    private function getRandomPosition(AxisAlignedBB $aabb, int $attempt = 0) : ?Position {
        if($attempt > 50) {
            return null;
        }

        $pos = new Position(mt_rand($aabb->minX, $aabb->maxX), 0, mt_rand($aabb->minZ, $aabb->maxZ), $this->world);
        $y = $this->world->getHighestBlockAt($pos->x, $pos->z);
        if($y === null) {
            return $this->getRandomPosition($aabb, ++$attempt);
        }
        $pos->y = $y;

        if($this->world->getBlock($pos) instanceof Ore) {
            return $pos;
        }
        return $this->getRandomPosition($aabb, ++$attempt);
    }

    public static function keyToID(string $type) {
        return match ($type) {
            "coal" => BlockLegacyIds::GRAY_GLAZED_TERRACOTTA,
            "iron" => BlockLegacyIds::SILVER_GLAZED_TERRACOTTA,
            "gold" => BlockLegacyIds::YELLOW_GLAZED_TERRACOTTA,
            "diamond" => BlockLegacyIds::LIGHT_BLUE_GLAZED_TERRACOTTA,
            "lapis" => BlockLegacyIds::BLUE_GLAZED_TERRACOTTA,
            "redstone" => BlockLegacyIds::RED_GLAZED_TERRACOTTA,
            "emerald" => BlockLegacyIds::GREEN_GLAZED_TERRACOTTA,
            default => BlockLegacyIds::GRAY_GLAZED_TERRACOTTA,
        };
    }

    public static function keyToAnimationID(string $type) {
        return match ($type) {
            "coal" => ItemIds::COAL,
            "iron" => ItemIds::IRON_INGOT,
            "gold" => ItemIds::GOLD_INGOT,
            "diamond" => ItemIds::DIAMOND,
            "lapis" => ItemIds::DYE,
            "redstone" => ItemIds::REDSTONE,
            "emerald" => ItemIds::EMERALD,
            default => ItemIds::COAL,
        };
    }

    public function setSpawnRate(int $spawnRate) : void {
        $this->spawnRate = $spawnRate;
    }

    /**
     * @return SpawnRushOreTask
     */
    public static function getInstance(): SpawnRushOreTask
    {
        return self::$instance;
    }

}