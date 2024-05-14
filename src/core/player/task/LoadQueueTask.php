<?php

declare(strict_types = 1);

namespace core\player\task;

use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Task;

class LoadQueueTask extends Task {

    /** @var NexusPlayer[] */
    private $queue;

    /** @var null|NexusPlayer */
    private $current = null;

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        if(empty($this->queue)) {
            return;
        }
        if($this->current !== null) {
            if($this->current->isOnline() === false or $this->current->isLoaded()) {
                $this->current = null;
                return;
            }
        }
        $this->current = array_shift($this->queue);
        if($this->current === null or ($this->current->isOnline() === false)) {
            $this->current = null;
            return;
        }
        $this->current->load(Nexus::getInstance());
    }

    /**
     * @param NexusPlayer $player
     */
    public function addToQueue(NexusPlayer $player): void {
        $this->queue[] = $player;
    }
}
