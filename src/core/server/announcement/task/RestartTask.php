<?php
declare(strict_types=1);

namespace core\server\announcement\task;

use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class RestartTask extends Task {

    const RESTART_INTERVAL = 14400;

    /** @var Nexus */
    private $core;

    /** @var int */
    private $time = self::RESTART_INTERVAL;

    /**
     * RestartTask constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @param int $currentTick
     *
     * @throws TranslationException
     */
    public function onRun(): void {
        $hours = floor($this->time / 3600);
        $minutes = floor(floor(($this->time / 60)) % 60);
        $seconds = $this->time % 60;
        if($hours == 0) {
            if(($minutes <= 5 and $seconds == 0) or ($minutes == 0 and $seconds <= 5)) {
                $this->core->getServer()->broadcastMessage(Translation::getMessage("restartMessage", [
                    "minutes" => TextFormat::WHITE . TextFormat::BOLD . $minutes,
                    "seconds" => TextFormat::WHITE . TextFormat::BOLD . $seconds
                ]));
            }
            if($minutes == 0 and $seconds == 5) {
                foreach($this->core->getServer()->getOnlinePlayers() as $player) {
                    if(!$player instanceof NexusPlayer) {
                        continue;
                    }
                    $player->removeCurrentWindow();
                }
            }
            if($minutes <= 0 and $seconds <= 0) {
                foreach($this->core->getServer()->getWorldManager()->getWorlds() as $level) {
                    $level->save(true);
                }
                foreach($this->core->getServer()->getOnlinePlayers() as $player) {
                    if(!$player instanceof NexusPlayer) {
                       continue;
                    }
                    $player->transfer("play.nexuspe.net", 19132, TextFormat::RESET . TextFormat::RED . "Server is restarting...");
                }
                $this->core->getServer()->shutdown();
            }
        }

        $this->time--;
    }

    /**
     * @param int $time
     */
    public function setRestartProgress(int $time): void {
        $this->time = $time;
    }

    /**
     * @return int
     */
    public function getRestartProgress(): int {
        return $this->time;
    }
}