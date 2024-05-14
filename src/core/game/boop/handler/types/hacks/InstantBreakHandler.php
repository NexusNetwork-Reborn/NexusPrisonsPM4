<?php

namespace core\game\boop\handler\types\hacks;

use core\player\NexusPlayer;
use core\game\boop\handler\Handler;
use core\player\PlayerManager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Event;

class InstantBreakHandler extends Handler {

    /**
     * @param NexusPlayer $player
     * @param Event $event
     */
    public function check(NexusPlayer $player, Event $event): void {
        if($event instanceof BlockBreakEvent) {
            if(!$event->getInstaBreak()) {
                if($player->isLoaded()) {
                    return;
                }
                if($player->hasCESession() && $player->getCESession()->hasPowerball()) {
                    return;
                }
                $handler = $this->core->getGameManager()->getBOOPManager()->getHandlerManager()->getBreakHandler();
                $time = $handler->getBreakTime($player);
                if($time === null) {
                    return;
                }
                $target = $event->getBlock();
                $expectedTime = PlayerManager::calculateBlockBreakTime($player, $target);
                $amount = 1;
                if($this->core->getServer()->getTicksPerSecond() < 19) {
                    $amount = 20 - floor($this->core->getServer()->getTicksPerSecond());
                }
                $expectedTime -= $amount;
                if($expectedTime < 20) {
                    return;
                }
                $actualTime = ceil(microtime(true) * 20) - $time;
                if($actualTime < $expectedTime) {
                    if(($expectedTime - $actualTime) > 40) {
                        $expectedSeconds = $expectedTime * 0.05;
                        $actualSeconds = $actualTime * 0.05;
                        $reason = "Insta-breaking. Expected time: $expectedSeconds" . "s, Actual time: $actualSeconds" . "s";
                        $this->handleViolations($player, $reason);
                        $event->cancel();
                    }
                }
            }
        }
    }
}