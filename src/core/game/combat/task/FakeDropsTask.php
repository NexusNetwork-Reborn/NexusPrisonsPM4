<?php

declare(strict_types = 1);

namespace core\game\combat\task;

use core\player\NexusPlayer;
use libs\utils\Task;
use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;

class FakeDropsTask extends Task {

    /** @var ItemEntity[] */
    private $drops = [];

    /**
     * FakeDropsTask constructor.
     *
     * @param NexusPlayer $player
     * @param Item[] $drops
     */
    public function __construct(NexusPlayer $player, array $drops) {
        foreach($drops as $item){
            $this->drops[] = $player->getWorld()->dropItem($player->getPosition(), $item, null, 10000);
        }
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        foreach($this->drops as $drop) {
            if($drop->isFlaggedForDespawn() or $drop->isClosed()) {
                continue;
            }
            $drop->flagForDespawn();
        }
    }

    public function onCancel(): void {
        foreach($this->drops as $drop) {
            if($drop->isFlaggedForDespawn() or $drop->isClosed()) {
                continue;
            }
            $drop->flagForDespawn();
        }
    }
}
