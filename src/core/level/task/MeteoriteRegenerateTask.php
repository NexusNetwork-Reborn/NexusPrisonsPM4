<?php
declare(strict_types=1);

namespace core\level\task;

use core\level\tile\Meteorite;
use libs\utils\Task;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\Position;

class MeteoriteRegenerateTask extends Task {

    /** @var Position */
    private $position;

    /** @var int */
    private $stack;

    /** @var bool */
    private $refined;

    /** @var bool */
    private $executed = false;

    /**
     * BlockRegenerateTask constructor.
     *
     * @param Position $position
     * @param int $stack
     */
    public function __construct(Position $position, int $stack, bool $refined) {
        $this->position = $position;
        $this->stack = $stack;
        $this->refined = $refined;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        $this->position->getWorld()->setBlock($this->position, VanillaBlocks::MAGMA());
        $tile = new Meteorite($this->position->getWorld(), $this->position);
        $tile->setStack($this->stack);
        $tile->setRefined($this->refined);
        $this->position->getWorld()->addTile($tile);
        $this->executed = true;
        $this->cancel();
    }

    public function onCancel(): void {
        if($this->executed === false) {
            $this->position->getWorld()->setBlock($this->position, VanillaBlocks::MAGMA());
            $tile = new Meteorite($this->position->getWorld(), $this->position);
            $tile->setStack($this->stack);
            $tile->setRefined($this->refined);
            $this->position->getWorld()->addTile($tile);
        }
    }
}
