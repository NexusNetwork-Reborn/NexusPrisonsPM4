<?php

namespace core\game\boop\handler\types;

use core\level\block\CrudeOre;
use core\level\block\Meteorite;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

class BreakHandler implements Listener {

    /** @var int[] */
    private $breakTimes = [];

    /** @var int[] */
    private $blocksBrokenOnClick = [];


    /** @var Nexus */
    private $core;

    /**
     * PearlHandler constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $core->getServer()->getPluginManager()->registerEvents($this, $core);
        $this->core = $core;
    }

    /**
     * @priority HIGHEST
     *
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($event->isCancelled()) {
            return;
        }
        if($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
            $this->breakTimes[$player->getUniqueId()->toString()] = floor(microtime(true) * 20);
            $this->blocksBrokenOnClick[$player->getUniqueId()->toString()] = 0;
        }
    }

    /**
     * @priority HIGH
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($event->isCancelled()) {
            return;
        }
        if($event->getInstaBreak()) {
            return;
        }
        $block = $event->getBlock();
        if($block instanceof CrudeOre or $block instanceof Meteorite) {
            return;
        }
        if(!isset($this->blocksBrokenOnClick[$player->getUniqueId()->toString()])) {
            $this->blocksBrokenOnClick[$player->getUniqueId()->toString()] = 0;
        }
        if(!$player->getCESession()->hasExplode() and !$player->getCESession()->hasPowerball()) {
            $this->blocksBrokenOnClick[$player->getUniqueId()->toString()]++;
        }
    }

    /**
     * @param NexusPlayer $player
     *
     * @return int
     */
    public function getBlocksBrokenOnClick(NexusPlayer $player): int {
        return $this->blocksBrokenOnClick[$player->getUniqueId()->toString()] ?? 0;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return int|null
     */
    public function getBreakTime(NexusPlayer $player): ?int {
        $time = $this->breakTimes[$player->getUniqueId()->toString()] ?? null;
        unset($this->breakTimes[$player->getUniqueId()->toString()]);
        return $time;
    }
}