<?php

namespace core\game\combat\koth;

use core\game\combat\koth\task\StartKOTHGameTask;
use core\Nexus;
use libs\utils\Task;

class KOTHSchedulingTask extends Task
{

    /** @var Nexus */
    private $core;

    /**
     * UpdateTask constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core)
    {
        $this->core = $core;
    }

    public function onRun(): void
    {
        if (date_default_timezone_get() !== "America/Los_Angeles") {
            date_default_timezone_set("America/Los_Angeles");
        }
        $time = explode(":", date("w:h:i:A", time()));
        $day = (int)$time[0];
        $hour = (int)$time[1];
        $minute = (int)$time[2];
        $type = $time[3];
        if ($day == 5 && $hour == 1 && $minute == 0 && $type == "PM") {
            $manager = $this->core->getGameManager()->getCombatManager();
            if ($manager->getKOTHGame() !== null) {
                $this->core->getLogger()->info("Tried auto-starting a KOTH game, but one is already running.");
                return;
            }
            $manager->initiateKOTHGame();
            $this->core->getScheduler()->scheduleRepeatingTask(new StartKOTHGameTask($this->core), 20);
        }
    }
}
