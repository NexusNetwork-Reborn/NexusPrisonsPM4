<?php

namespace core\display\animation;

use core\display\animation\entity\AnimationEntity;
use core\player\NexusPlayer;

abstract class Animation {

    /** @var NexusPlayer */
    protected $owner;

    /** @var bool */
    protected $closed = false;

    /**
     * Animation constructor.
     *
     * @param NexusPlayer $owner
     */
    public function __construct(NexusPlayer $owner) {
        $this->owner = $owner;
    }

    /**
     * @return NexusPlayer
     */
    public function getOwner(): NexusPlayer {
        return $this->owner;
    }

    public function closeAnimation(): void {
        $this->closed = true;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool {
        return $this->closed;
    }

    /**
     * @param NexusPlayer $player
     */
    abstract public function sendTo(NexusPlayer $player): void;

    abstract public function tick(): void;

    /**
     * @return AnimationEntity
     */
    abstract public function getEntity(): AnimationEntity;
}