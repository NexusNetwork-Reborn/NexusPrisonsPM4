<?php

declare(strict_types = 1);

namespace core\game\combat\koth\task;

use core\Nexus;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class StartKOTHGameTask extends Task {

    /** @var Nexus */
    private $core;

    /** @var int */
    private $time = 60;

    /**
     * StartKOTHGameTask constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @throws TranslationException
     */
    public function onRun(): void {
        if($this->time >= 0) {
            if($this->time % 10 == 0 or $this->time <= 5) {
                $this->core->getServer()->broadcastMessage(Translation::getMessage("kothStart", [
                    "amount" => TextFormat::AQUA . $this->time
                ]));
            }
            $this->time--;
            return;
        }
        $this->core->getGameManager()->getCombatManager()->startKOTHGame();
        $this->getHandler()->cancel();
        //$this->core->getScheduler()->cancelTask($this->getTaskId());
    }
}