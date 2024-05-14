<?php

declare(strict_types = 1);

namespace core\player\task;

use core\game\combat\CombatManager;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Task;

class SetGuardTaxHeartbeatTask extends Task {

    /** @var Nexus */
    private $core;

    /** @var NexusPlayer[] */
    private $players;

    /**
     * UpdateTask constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->players = $core->getServer()->getOnlinePlayers();
    }

    public function onRun(): void {
        if(empty($this->players)) {
            $this->players = $this->core->getServer()->getOnlinePlayers();
        }
        $player = array_shift($this->players);
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($player->isOnline() === false) {
            return;
        }
        if($player->isLoaded() === false) {
            return;
        }
        $player->setGuardTax(CombatManager::calculateGuardTax($player));
    }
}