<?php

namespace core\game\boop\handler\task;

use core\Nexus;
use core\player\NexusPlayer;
use core\game\boop\handler\HandlerManager;
use core\game\boop\task\CheatLogTask;
use core\player\rank\Rank;
use libs\utils\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ResetViolationsTask extends Task {

    /** @var HandlerManager */
    private $manager;

    /**
     * ResetViolationsTask constructor.
     *
     * @param HandlerManager $manager
     */
    public function __construct(HandlerManager $manager) {
        $this->manager = $manager;
    }

    public function onRun(): void {
        $this->manager->getAutoClickerHandler()->resetViolations();
        $this->manager->getFlyHandler()->resetViolations();
        $this->manager->getInstantBreakHandler()->resetViolations();
        $this->manager->getNukeHandler()->resetViolations();
        $message = TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "BOOP" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::YELLOW . "Violations have been reset!";
        /** @var NexusPlayer $onlinePlayer */
        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            if($onlinePlayer->isLoaded() === false) {
                continue;
            }
            $rank = $onlinePlayer->getDataSession()->getRank();
            if($rank->getIdentifier() < Rank::TRAINEE or $rank->getIdentifier() > Rank::EXECUTIVE) {
                continue;
            }
            $onlinePlayer->sendMessage($message);
        }
        Nexus::getInstance()->getLogger()->info($message);
        Server::getInstance()->getAsyncPool()->increaseSize(2);
        Server::getInstance()->getAsyncPool()->submitTaskToWorker(new CheatLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()) . TextFormat::clean($message)), 1);
    }
}