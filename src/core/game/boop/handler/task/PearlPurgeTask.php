<?php

namespace core\game\boop\handler\task;

use core\game\boop\handler\types\PearlHandler;
use libs\utils\Task;

class PearlPurgeTask extends Task {

    /** @var PearlHandler */
    private $handler;

    /**
     * PearlPurgeTask constructor.
     *
     * @param PearlHandler $handler
     */
    public function __construct(PearlHandler $handler) {
        $this->handler = $handler;
    }

    public function onRun(): void {
        $this->handler->purge();
    }
}