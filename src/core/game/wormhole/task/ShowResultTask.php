<?php

namespace core\game\wormhole\task;

use core\display\animation\entity\ItemBaseEntity;
use core\game\wormhole\WormholeSession;
use libs\utils\Task;
use pocketmine\world\Position;

class ShowResultTask extends Task {

    /** @var int */
    private $runs;

    /** @var WormholeSession */
    private $session;

    /**
     * ShowResultTask constructor.
     *
     * @param WormholeSession $session
     * @param int $runs
     */
    public function __construct(WormholeSession $session, int $runs = 50) {
        $this->session = $session;
        $this->runs = $runs;
    }

    public function onRun(): void {
        $owner = $this->session->getOwner();
        if((!$owner->isOnline()) or $owner->isClosed()) {
            $this->cancel();
            return;
        }
        /** @var ItemBaseEntity $entity */
        $entity = $this->session->getAnimation()->getEntity();
        if(--$this->runs <= 0) {
            $this->cancel();
            if($entity->isClosed()) {
                return;
            }
            $entity->flagForDespawn();
            return;
        }
        $directionVector = $owner->getDirectionVector()->multiply(3);
        $position = Position::fromObject($owner->getPosition()->add($directionVector->x, $directionVector->y + $owner->getEyeHeight() + 1, $directionVector->z), $owner->getWorld());
        $entity->initialize($position, 2, false);
    }
}