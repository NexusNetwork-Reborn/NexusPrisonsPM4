<?php

namespace core\display\animation\task;

use core\display\animation\AnimationManager;
use libs\utils\Task;

class AnimationHeartbeatTask extends Task {

    /** @var AnimationManager */
    private $manager;

    /**
     * AuctionHeartbeatTask constructor.
     *
     * @param AnimationManager $manager
     */
    public function __construct(AnimationManager $manager) {
        $this->manager = $manager;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        $this->manager->tickAnimations();
    }
}