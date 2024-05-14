<?php
declare(strict_types=1);

namespace core\server\announcement;

use core\Nexus;
use core\server\announcement\task\RestartTask;

class AnnouncementManager {

    /** @var Nexus */
    private $core;

    /** @var RestartTask */
    private $restarter;

    /** @var string[] */
    private $messages;

    /** @var int */
    private $currentId = 0;

    /**
     * AnnouncementManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->restarter = new RestartTask($core);
        $this->init();
        //$core->getScheduler()->scheduleRepeatingTask(new BroadcastMessagesTask($core), 4800);
        $core->getScheduler()->scheduleRepeatingTask($this->restarter, 20);
    }

    public function init(): void {
        $this->messages = [
            ""
        ];
    }

    /**
     * @return string
     */
    public function getNextMessage(): string {
        if(isset($this->messages[$this->currentId])) {
            $message = $this->messages[$this->currentId];
            $this->currentId++;
            return $message;
        }
        $this->currentId = 0;
        return $this->messages[$this->currentId];
    }

    /**
     * @return RestartTask
     */
    public function getRestarter(): RestartTask {
        return $this->restarter;
    }
}
