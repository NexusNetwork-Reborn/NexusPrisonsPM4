<?php

namespace core\game\boop\handler\types\hacks;

use core\player\NexusPlayer;
use core\game\boop\handler\Handler;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Event;

class NukeHandler extends Handler {

    /**
     * @param NexusPlayer $player
     * @param Event $event
     */
    public function check(NexusPlayer $player, Event $event): void {
        if($event instanceof BlockBreakEvent) {
            // TODO
            if(!$player->isLoaded()) {
                $event->cancel();
                return;
            }
            if(!$event->getInstaBreak()) {
                if($player->getCESession()->hasExplode()) {
                    return;
                }
                if($player->getCESession()->hasPowerball()) {
                    return;
                }
                $handler = $this->core->getGameManager()->getBOOPManager()->getHandlerManager()->getBreakHandler();
                $blocksBroken = $handler->getBlocksBrokenOnClick($player);
                if($blocksBroken > 10) {
                    $reason = "Nuke. Many blocks broken in one action";
                    $this->handleViolations($player, $reason);
                    $event->cancel();
                }
            }
        }
    }
}