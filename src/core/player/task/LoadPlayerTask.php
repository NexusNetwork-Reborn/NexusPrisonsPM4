<?php
declare(strict_types=1);

namespace core\player\task;

use core\Nexus;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use core\provider\event\PlayerLoadEvent;
use core\translation\Translation;
use libs\utils\Task;
use libs\utils\Utils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\utils\TextFormat;

class LoadPlayerTask extends Task {

    /** @var NexusPlayer */
    private $player;

    /** @var int */
    private $time;

    /** @var int */
    private $maxTime;

    /**
     * LoadScreenTask constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $this->player = $player;
        $this->time = 120;
        $this->maxTime = 120;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        if($this->player === null or $this->player->isOnline() === false) {
            $this->cancel();
            return;
        }
        if($this->player->isLoaded() === true and $this->player->spawned === true) {
            if(!$this->player->getScoreboard()->isSpawned()) {
                $this->player->initializeScoreboard();
                $this->player->initializeFloatingTexts();
                $this->player->getBossBar()->spawn();
                $percent = XPUtils::getXPProgress($this->player->getDataSession()->getXP(), $this->player->getDataSession()->getPrestige(), RPGManager::MODIFIER, 0);
                $this->player->getBossBar()->update($this->player->getDataSession()->getLevelTag() . TextFormat::YELLOW . " ($percent%%%%)", $percent);
            }
            //$this->player->playBlastSound();
            $this->player->getCore()->getScheduler()->scheduleDelayedTask(new class($this->player) extends Task {

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
                    if(count($this->player->getDataSession()->getInbox()->getInventory()->getContents()) > 0) {
                        $this->player->sendTranslatedMessage("inboxAlert");
                    }
                    $this->player->sendMessage(" ");
                    if($this->player->getDataSession()->getRestedXP() > 0) {
                        $this->player->sendMessage(TextFormat::BOLD . TextFormat::GREEN . "(!) Rested XP" . TextFormat::RESET . TextFormat::GREEN . " has " . Utils::secondsToTime($this->player->getDataSession()->getRestedXP()) . " remaining - " . TextFormat::BOLD . "1.5x XP");
                    }
                    else {
                        $this->player->sendMessage(TextFormat::BOLD . TextFormat::RED . "(!) Rested XP" . TextFormat::RESET . TextFormat::RED . " has " . Utils::secondsToTime($this->player->getDataSession()->getRestedXP()) . " remaining - " . TextFormat::BOLD . "1.0x XP");
                    }
                    $this->player->sendMessage(TextFormat::GRAY . "You accrue Rested XP while logged out, and use it while being logged in.");
                    $this->player->sendMessage(" ");
                    if(!$this->player->hasPlayedBefore()) {
                        $this->player->getDataSession()->setEnergyModifier(4.0, 1800);
                        $this->player->getDataSession()->setXPModifier(3.0, 1800);
                    }
                    if($this->player->getDataSession()->getXPBoostTimeLeft() > 0) {
                        $modifier = $this->player->getDataSession()->getBaseXPModifier();
                        $this->player->sendMessage(Translation::ORANGE . "You have an active " . number_format($modifier, 2) . "x XP Booster for: " . TextFormat::GREEN . Utils::secondsToTime($this->player->getDataSession()->getXPBoostTimeLeft()));
                    }
                    if($this->player->getDataSession()->getEnergyBoostTimeLeft() > 0) {
                        $modifier = $this->player->getDataSession()->getEnergyModifier();
                        $this->player->sendMessage(Translation::ORANGE . "You have an active " . number_format($modifier, 2) . "x Energy Booster for: " . TextFormat::GREEN . Utils::secondsToTime($this->player->getDataSession()->getEnergyBoostTimeLeft()));
                    }
                }
            }, 1);
//            $this->player->getCore()->getScheduler()->scheduleDelayedTask(new class($this->player) extends Task {
//
//                /** @var NexusPlayer */
//                private $player;
//
//                /**
//                 *  constructor.
//                 *
//                 * @param NexusPlayer $player
//                 */
//                public function __construct(NexusPlayer $player) {
//                    $this->player = $player;
//                }
//
//                /**
//                 * @param int $currentTick
//                 */
//                public function onRun(): void {
//                    if($this->player->isOnline() === false) {
//                        return;
//                    }
//                    $this->player->playTwinkleSound();
//                    $this->player->sendTitle(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Aethic" . TextFormat::DARK_AQUA . " Prisons", TextFormat::RESET . TextFormat::GRAY . "Space-Age confinement, Minecraft Prisons", 5, 20, 5);
//                }
//            }, 20);
            $event = new PlayerLoadEvent($this->player);
            $event->call();
            $this->player->getDataSession()->setLoadTime(time());
            $this->cancel();
            return;
        }
        if($this->time >= 0) {
            $this->time--;
            return;
        }
        $this->player->kickDelay(TextFormat::RED . "Loading timed out. Rejoin to load again!");
        return;
    }
}
