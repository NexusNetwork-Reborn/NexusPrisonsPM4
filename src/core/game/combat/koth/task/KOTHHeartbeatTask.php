<?php

declare(strict_types = 1);

namespace core\game\combat\koth\task;

use core\game\combat\CombatManager;
use core\translation\TranslationException;
use pocketmine\scheduler\Task;

class KOTHHeartbeatTask extends Task {

    /** @var CombatManager */
    private $manager;

    /**
     * KOTHHeartbeatTask constructor.
     *
     * @param CombatManager $manager
     */
    public function __construct(CombatManager $manager) {
        $this->manager = $manager;
    }

    /**
     * @param int $currentTick
     *
     * @throws TranslationException
     */
    public function onRun(): void {
        $game = $this->manager->getKOTHGame();
        if($game !== null and $game->hasStarted()) {
            $game->tick();
        }
    }
}
