<?php

namespace core\game\gamble;

use core\Nexus;
use core\player\NexusPlayer;
use core\provider\event\PlayerLoadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

class GambleListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * GambleListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     * @param PlayerLoadEvent $event
     */
    public function onPlayerLoad(PlayerLoadEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $this->core->getGameManager()->getGambleManager()->createRecord($player);
    }

    /**
     * @priority NORMAL
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $active = $this->core->getGameManager()->getGambleManager()->getActiveCoinFlip($player);
        if($active !== null) {
            $active->finalRoll();
        }
        $coinFlip = $this->core->getGameManager()->getGambleManager()->getCoinFlip($player);
        if($coinFlip !== null) {
            $player->getDataSession()->addToBalance($coinFlip->getAmount());
            $this->core->getGameManager()->getGambleManager()->removeCoinFlip($player);
        }
    }
}