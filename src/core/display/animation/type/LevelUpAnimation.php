<?php

namespace core\display\animation\type;

use core\display\animation\Animation;
use core\display\animation\AnimationException;
use core\display\animation\entity\AnimationEntity;
use core\display\animation\entity\LevelUpEntity;
use core\player\NexusPlayer;

class LevelUpAnimation extends Animation {

    /** @var LevelUpEntity */
    protected $item;

    /**
     * LevelUpAnimation constructor.
     *
     * @param NexusPlayer $owner
     *
     * @throws AnimationException
     */
    public function __construct(NexusPlayer $owner) {
        $this->item = LevelUpEntity::create($owner);
        $this->item->spawnTo($owner);
        parent::__construct($owner);
    }

    /**
     * @param NexusPlayer $player
     */
    public function sendTo(NexusPlayer $player): void {

    }

    public function tick(): void {
        if($this->owner->isClosed()) {
            $this->item->flagForDespawn();
            $this->closeAnimation();
            return;
        }
        if($this->item->isFlaggedForDespawn() or $this->item->isClosed()) {
            $this->closeAnimation();
        }
    }

    /**
     * @return AnimationEntity
     */
    public function getEntity(): AnimationEntity {
        return $this->item;
    }
}