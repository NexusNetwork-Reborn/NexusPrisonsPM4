<?php

namespace core\game\blackAuction\task;

use core\game\blackAuction\BlackAuctionManager;
use libs\utils\Task;

class BlackAuctionHeartbeatTask extends Task {

    /** @var BlackAuctionManager */
    private $manager;

    /**
     * BlackAuctionHeartbeatTask constructor.
     *
     * @param BlackAuctionManager $manager
     */
    public function __construct(BlackAuctionManager $manager) {
        $this->manager = $manager;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        $active = $this->manager->getActiveAuction();
        if($active !== null) {
            if($active->getTimeLeft() <= 0) {
                $active->sell();
            }
            if($active->getTimeLeft() === 60) {
                $active->announceAlert();
            }
            return;
        }
        if($this->manager->getTimeBeforeNext() <= 0) {
            $this->manager->startAuction();
        }
    }
}