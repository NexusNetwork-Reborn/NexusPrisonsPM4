<?php
declare(strict_types=1);

namespace core\level\task;

use libs\utils\Task;
use pocketmine\block\Block;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BlockPlaceSound;

class BlockRegenerateTask extends Task {

    /** @var Block */
    private $block;

    /** @var Position */
    private $position;

    /** @var bool */
    private $executed = false;

    /**
     * BlockRegenerateTask constructor.
     *
     * @param Block $block
     * @param Position $position
     */
    public function __construct(Block $block, Position $position) {
        $this->block = $block;
        $this->position = $position;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        $world = $this->position->getWorld();
        $world->setBlock($this->position, $this->block);
        $world->addParticle($this->position, new BlockBreakParticle($this->block));
        $world->addSound($this->position, new BlockPlaceSound($this->block));
        $this->executed = true;
        $this->cancel();
    }

    public function onCancel(): void {
        if($this->executed === false) {
            $world = $this->position->getWorld();
            $world->setBlock($this->position, $this->block);
        }
    }
}
