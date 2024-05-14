<?php
declare(strict_types=1);

namespace core\player\task;

use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Task;

class SavePlayersTask extends Task {

    /** @var Nexus */
    private $core;

    /**
     * SavePlayersTask constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @param int $tick
     */
    public function onRun(): void {
        $start = microtime(true);
        /** @var NexusPlayer $player */
        foreach($this->core->getServer()->getOnlinePlayers() as $player) {
            if($player->isLoaded()) {
                $player->getDataSession()->saveDataAsync();
            }
        }
        $time = (microtime(true) - $start);
        $this->core->getLogger()->notice("[Auto Save] Save completed in " . ($time >= 1 ? round($time, 3) . "s" : round($time * 1000) . "ms"));
    }
}