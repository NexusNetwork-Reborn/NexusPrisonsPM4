<?php

declare(strict_types = 1);

namespace core\game\trade\task;

use core\game\trade\TradeManager;
use core\translation\TranslationException;
use pocketmine\scheduler\Task;

class TradeHeartbeatTask extends Task {

    /** @var TradeManager */
    private $manager;

    /**
     * TradeHeartbeatTask constructor.
     *
     * @param TradeManager $manager
     */
    public function __construct(TradeManager $manager) {
        $this->manager = $manager;
    }

    /**
     * @param int $currentTick
     *
     * @throws TranslationException
     */
    public function onRun(): void {
        foreach($this->manager->getSessions() as $key => $session) {
            $session->tick($key, $this->manager);
        }
    }
}
