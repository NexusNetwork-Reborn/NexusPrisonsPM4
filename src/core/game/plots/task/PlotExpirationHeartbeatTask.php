<?php

namespace core\game\plots\task;

use core\game\plots\PlotManager;
use libs\utils\Task;

class PlotExpirationHeartbeatTask extends Task {

    /** @var PlotManager */
    private $manager;

    /**
     * PlotExpirationHeartbeatTask constructor.
     *
     * @param PlotManager $manager
     */
    public function __construct(PlotManager $manager) {
        $this->manager = $manager;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        foreach($this->manager->getPlots() as $plot) {
            $plot->tickExpiration();
        }
    }
}