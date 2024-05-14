<?php

declare(strict_types = 1);

namespace core\game\trade;

use core\game\trade\task\TradeHeartbeatTask;
use core\Nexus;

class TradeManager {

    /** @var Nexus */
    private $core;

    /** @var TradeSession[] */
    private $sessions = [];

    /**
     * TradeManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getScheduler()->scheduleRepeatingTask(new TradeHeartbeatTask($this), 20);
    }

    /**
     * @param TradeSession $session
     */
    public function addSession(TradeSession $session): void {
        $this->sessions[] = $session;
    }

    /**
     * @param int $key
     */
    public function removeSession(int $key): void {
        if(isset($this->sessions[$key])) {
            unset($this->sessions[$key]);
        }
    }

    /**
     * @return TradeSession[]
     */
    public function getSessions(): array {
        return $this->sessions;
    }
}