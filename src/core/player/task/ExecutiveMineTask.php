<?php

namespace core\player\task;

use core\command\inventory\WarpMenuInventory;
use core\game\wormhole\entity\ExecutiveEnderman;
use core\level\LevelManager;
use core\Nexus;
use core\player\DataSession;
use core\player\NexusPlayer;
use libs\utils\Task;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class ExecutiveMineTask extends Task {

    private const MAX_ENDERMEN = 10;

    public static $endermanConter = 0;

    /** @var Nexus */
    private $core;

    private $player;

    private $blockCounter = 0;

    private $currentThreshold;

    private $thresholdReached = false;

    private $wormholeUnlocked = false;

    private $timeLeft;

    private $totalTime;

    private $boosted = false;

    private $boost = 0;

    public function __construct(Nexus $core, NexusPlayer $player) {
        $this->core = $core;
        $this->player = $player;
        $this->currentThreshold = $player->getDataSession()->getThresholdUpgradeQuota();
        $this->timeLeft = $player->getDataSession()->getExecutiveMineTimeInSeconds();
        $this->totalTime = $this->timeLeft / 60;
        $player->getDataSession()->clearExecutiveMineTime();
        $player->sendMessage($this->getGoalMessage());
    }

    public function addTimeLeft(int $time) {
        $this->timeLeft += $time;
        $this->totalTime += $time / 60;
        $this->boosted = true;
        $this->boost = $time;
    }

    public function isBoosted(): bool
    {
        return $this->boosted;
    }

    public function getBoost(): int
    {
        return $this->boost;
    }

    public function onRun(): void
    {
        $this->timeLeft--;
        if($this->timeLeft >= 60) {
            $time = TextFormat::YELLOW . intval($this->timeLeft / 60) . " minutes, and " . ($this->timeLeft % 60) . " seconds";
        } else {
            $time = TextFormat::RED . $this->timeLeft . " seconds";
        }
        if($this->player->isConnected()) {
            if (!$this->thresholdReached) {
                $this->player->sendTip(TextFormat::GREEN . "Time Left: " . TextFormat::RESET . $time . "\n" . TextFormat::GOLD . "Block Threshold: " . TextFormat::YELLOW . ($this->currentThreshold - $this->blockCounter));
            } else {
                $this->player->sendTip(TextFormat::GREEN . "Time Left: " . TextFormat::RESET . $time . "\n" . TextFormat::GOLD . "Block Threshold: " . TextFormat::GREEN . "REACHED");
            }
        }
        if($this->timeLeft % 30 === 0) {
            $position = $this->generateRandomPosition();
            if($position !== null && self::$endermanConter < self::MAX_ENDERMEN) {
                self::$endermanConter++;
                $enderman = new ExecutiveEnderman(Location::fromObject($position, $this->player->getWorld()), new CompoundTag());
                $enderman->spawnToAll();
                $this->core->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($enderman) : void {
                    if(!$enderman->isClosed() && !$enderman->isFlaggedForDespawn()) {
                        $enderman->flagForDespawn();
                        self::$endermanConter--;
                    }
                }), 20 * 300);
            }
        }
        if($this->timeLeft <= 0 || $this->player->getWorld()->getFolderName() !== "executive") {
            $this->endExecutiveSession();
            $this->cancel();
        }
    }

    public function addBlock(): void
    {
        $this->blockCounter++;
        if(!$this->thresholdReached && $this->blockCounter >= $this->currentThreshold) {
            $this->thresholdReached = true;
            $this->player->getDataSession()->addThreshold(120);
            $this->player->sendMessage(TextFormat::GREEN . "Because you reached the threshold, the next time you return you will have a higher threshold and more\ntime to mine! See " . TextFormat::AQUA . "/executive" . TextFormat::GREEN . " goal for more details.");
        }
        $this->blockCounter = min($this->blockCounter, $this->currentThreshold);
    }

    public function endExecutiveSession(): void
    {
        if(!$this->thresholdReached) {
            $this->player->getDataSession()->reduceThreshold(120);
        }
        if($this->player->isConnected()) {
            if(isset(WarpMenuInventory::$executiveSessions[$this->player->getXuid()])) {
                unset(WarpMenuInventory::$executiveSessions[$this->player->getXuid()]);
            }
            $this->player->teleport($this->core->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
            $this->player->getInventory()->remove(VanillaBlocks::END_STONE()->asItem());
        }
    }

    public function getGoalMessage() : string {
        if($this->timeLeft >= 60) {
            $time = intval($this->timeLeft / 60) . " minutes, and " . ($this->timeLeft % 60) . " seconds";
        } else {
            $time = $this->timeLeft . " seconds";
        }
        $message = TextFormat::BOLD . TextFormat::GOLD . "(!) " . TextFormat::RESET . TextFormat::GOLD . "Executive Mine Goals";
        $message .= "\nTime Remaining: " . TextFormat::RESET . $time;
        $message .= TextFormat::GOLD . "\nBlock Threshold: " . TextFormat::RESET . ($this->currentThreshold - $this->blockCounter);
        $message .= "\n";
        $message .= TextFormat::GRAY . "If you reach the Block Threshold for the day, it will increase your Time Limit for the following day by " . TextFormat::UNDERLINE . "2";
        $message .= "\nminutes" . TextFormat::RESET . TextFormat::GRAY . ", and your Block Threshold by " . TextFormat::UNDERLINE . (DataSession::BLOCKS_PER_MINUTE * 2);
        $message .= TextFormat::RESET . "\n";
        $message .= TextFormat::GRAY . "If you fail to reach the Block Threshold for a day, they will be " . TextFormat::UNDERLINE . "decreased" . TextFormat::RESET . TextFormat::GRAY . " by the same amounts.";
        $message .= "\n";
        $message .= TextFormat::GRAY . "The next time the clock strikes 12PM PST you will have:";
        if($this->thresholdReached) {
            $message .= "\nTime Limit: " . TextFormat::RESET . ($this->totalTime + 2) . " minutes";
        } else {
            $message .= "\nTime Limit: " . TextFormat::RESET . $this->totalTime . " minutes";
        }
        $message .= TextFormat::GRAY . "\nBlock Threshold: " . TextFormat::RESET . $this->player->getDataSession()->getThresholdUpgradeQuota();
        return $message;
    }

    public function getTimeLeft(): int
    {
        return $this->timeLeft;
    }

    public function isWormholeUnlocked() : bool {
        return $this->wormholeUnlocked;
    }

    public function unlockWormhole() : void {
        $this->wormholeUnlocked = true;
    }

    private function generateRandomPosition(int $cycle = 0) : ?Position {
        if($cycle >= 16) {
            return null;
        }
        $pos = $this->player->getPosition();
        $playerX = (int) $pos->getFloorX();
        $playerY = (int) $pos->getFloorY();
        $playerZ = (int) $pos->getFloorZ();
        $x = mt_rand($playerX - 4, $playerX + 4);
        $y = $playerY + 2;
        $z = mt_rand($playerZ - 4, $playerZ + 4);
        $position = new Position($x, $y, $z, $this->player->getWorld());
        if($this->player->getWorld()->getBlock($position)->getId() !== VanillaBlocks::AIR()->getId()) {
            return $this->generateRandomPosition($cycle + 1);
        }
        return $position;
    }
}