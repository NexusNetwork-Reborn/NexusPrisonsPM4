<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\task;

use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Task;
use libs\utils\Utils;
use pocketmine\utils\TextFormat;

class ShowMomentumTask extends Task {

    /** @var Nexus */
    private $core;

    /**
     * ShowMomentumTask constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        /** @var NexusPlayer $player */
        foreach($this->core->getServer()->getOnlinePlayers() as $player) {
            if(!$player->isLoaded()) {
                return;
            }
            $session = $player->getCESession();
            if($session->hasMomentum()) {
                if($session->getMomentum() > 0) {
                    if($player->getScoreboard()->getLine(11) === null) {
                        $player->getScoreboard()->setScoreLine(11, "");
                    }
                    if($player->getScoreboard()->getLine(12) === null) {
                        $player->getScoreboard()->setScoreLine(12, TextFormat::GREEN . TextFormat::BOLD . "Momentum");
                    }
                    $time = Utils::secondsToTime($session->getMomentum());
                    $player->getScoreboard()->setScoreLine(13, TextFormat::GREEN . "   +" . $session->getMomentumSpeed() . "%%%%" . TextFormat::GRAY . " (" . TextFormat::WHITE . $time . TextFormat::GRAY . ")");
                }
            }
            else {
                if($player->getScoreboard()->getLine(11) !== null) {
                    $player->getScoreboard()->removeLine(11);
                }
                if($player->getScoreboard()->getLine(12) !== null) {
                    $player->getScoreboard()->removeLine(12);
                }
                if($player->getScoreboard()->getLine(13) !== null) {
                    $player->getScoreboard()->removeLine(13);
                }
            }
        }
    }
}
