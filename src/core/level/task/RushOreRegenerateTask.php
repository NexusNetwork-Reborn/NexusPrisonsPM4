<?php
declare(strict_types=1);

namespace core\level\task;

use core\level\tile\RushOreBlock;
use libs\utils\Task;
use pocketmine\block\BlockFactory;
use pocketmine\world\Position;

class RushOreRegenerateTask extends Task {

    /** @var Position */
    private $position;

    /** @var int */
    private $hits;

    /** @var bool */
    private $executed = false;

    /** @var \core\level\block\RushOreBlock */
    private $block;

    /**
     * RushOreRegenerateTask constructor.
     *
     * @param Position $position
     * @param int $hits
     * @param \core\level\block\RushOreBlock $block
     */
    public function __construct(Position $position, int $hits, \core\level\block\RushOreBlock $block) {
        $this->position = $position;
        $this->hits = $hits;
        $this->block = $block;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        $this->position->getWorld()->setBlock($this->position, $this->block);
        $tile = new RushOreBlock($this->position->getWorld(), $this->position);
        $tile->setHits($this->hits);
        $current = $this->position->getWorld()->getTile($this->position);
        if($current !== null) {
            if($current instanceof RushOreBlock) {
                $current->setHits($this->hits);
            }
            return; // Correct?
        }
        $this->position->getWorld()->addTile($tile);
        $this->executed = true;
        $this->cancel();
    }

}
