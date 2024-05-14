<?php
declare(strict_types=1);

namespace core\level\task;

use core\level\tile\CrudeOre;
use libs\utils\Task;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\Position;

class CrudeOreRegenerateTask extends Task {

    /** @var Position */
    private $position;

    /** @var int */
    private $ore;

    /** @var float */
    private $amount;

    /** @var string */
    private $rarity;

    /** @var bool */
    private $refined;

    /** @var bool */
    private $executed = false;

    /**
     * CrudeOreRegenerateTask constructor.
     *
     * @param Position $position
     * @param int $ore
     * @param float $amount
     * @param string $rarity
     * @param bool $refined
     */
    public function __construct(Position $position, int $ore, float $amount, string $rarity, bool $refined) {
        $this->position = $position;
        $this->ore = $ore;
        $this->amount = $amount;
        $this->rarity = $rarity;
        $this->refined = $refined;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        $this->position->getWorld()->setBlock($this->position, VanillaBlocks::NETHER_QUARTZ_ORE());
        $tile = new CrudeOre($this->position->getWorld(), $this->position);
        $tile->setOre($this->ore);
        $tile->setAmount($this->amount);
        $tile->setRarity($this->rarity);
        $tile->setRefined($this->refined);
        $this->position->getWorld()->addTile($tile);
        $this->executed = true;
        $this->cancel();
    }

    public function onCancel(): void {
        if($this->executed === false) {
            $this->position->getWorld()->setBlock($this->position, VanillaBlocks::NETHER_QUARTZ_ORE());
            $tile = new CrudeOre($this->position->getWorld(), $this->position);
            $tile->setOre($this->ore);
            $tile->setAmount($this->amount);
            $tile->setRarity($this->rarity);
            $tile->setRefined($this->refined);
            $this->position->getWorld()->addTile($tile);
        }
    }
}
