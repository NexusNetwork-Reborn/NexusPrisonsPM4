<?php

declare(strict_types = 1);

namespace core\level\task;

use core\game\item\enchantment\PickaxeExplosion;
use pocketmine\scheduler\Task;
use SplFixedArray;
use SplQueue;

class ExplosionQueueTask extends Task {

    /**
     * Maximum amount of explosions
     * processed each tick.
     */
    const MAX_CONCURRENT_EXPLOSIONS = 4;

    /** @var SplFixedArray */
    private $ongoing;

    /** @var SplQueue */
    private $queue;

    /**
     * ExplosionQueue constructor.
     */
    public function __construct() {
        $this->ongoing = new SplFixedArray(self::MAX_CONCURRENT_EXPLOSIONS);
        $this->queue = new SplQueue();
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        if($this->queue->isEmpty()) {
            return;
        }
        $explosion = $this->queue->pop();
        if($explosion !== null) {
            $explosion->explodeA();
            $explosion->explodeB();
        }
    }

    /**
     * @param PickaxeExplosion $explosion
     */
    public function add(PickaxeExplosion $explosion): void {
        $this->queue->enqueue($explosion);
    }
}