<?php

namespace core\game\wormhole\task;

use core\display\animation\entity\ItemBaseEntity;
use core\game\wormhole\WormholeSession;
use libs\utils\Task;
use pocketmine\world\Position;

class InitializePositionTask extends Task {

    /** @var WormholeSession */
    private $session;

    /** @var Position */
    private $position;

    /** @var float */
    private $speed;

    /** @var bool */
    private $scheduleDespawn;

    /** @var int */
    private $delay;

    /** @var null|callable */
    private $callable;

    /** @var null|callable */
    private $onFinish;

    /**
     * InitializePositionTask constructor.
     *
     * @param WormholeSession $session
     * @param Position $position
     * @param float $speed
     * @param bool $scheduleDespawn
     * @param int $delay
     * @param callable|null $callable
     * @param callable|null $onFinish
     */
    public function __construct(WormholeSession $session, Position $position, float $speed, bool $scheduleDespawn, int $delay, ?callable $callable = null, ?callable $onFinish = null) {
        $this->session = $session;
        $this->position = $position;
        $this->speed = $speed;
        $this->scheduleDespawn = $scheduleDespawn;
        $this->delay = $delay;
        $this->callable = $callable;
        $this->onFinish = $onFinish;
    }

    public function onRun(): void {
        $owner = $this->session->getOwner();
        if($owner->isOnline() === false) {
            $this->cancel();
            return;
        }
        /** @var ItemBaseEntity $entity */
        $entity = $this->session->getAnimation()->getEntity();
        $entity->initialize($this->position, $this->speed, $this->scheduleDespawn, $this->delay, $this->callable, $this->onFinish);
    }
}