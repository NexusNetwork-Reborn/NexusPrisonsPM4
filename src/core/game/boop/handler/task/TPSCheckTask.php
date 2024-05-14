<?php

namespace core\game\boop\handler\task;

use core\Nexus;
use libs\utils\Task;

class TPSCheckTask extends Task {

    /** @var Nexus */
    private $core;

    /**
     * TPSCheckTask constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    public function onRun(): void {
        $handlerManager = $this->core->getGameManager()->getBOOPManager()->getHandlerManager();
        if($this->core->getServer()->getTicksPerSecondAverage() > 18) {
            if($handlerManager->isHalted()) {
                $handlerManager->setHalted(false);
            }
            return;
        }
        if(!$handlerManager->isHalted()) {
            $handlerManager->setHalted(true);
        }
    }
}