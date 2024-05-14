<?php

declare(strict_types = 1);

namespace core\game\boop\handler;

use core\Nexus;
use core\game\boop\handler\task\ResetViolationsTask;
use core\game\boop\handler\types\AttackHandler;
use core\game\boop\handler\types\BreakHandler;
use core\game\boop\handler\types\hacks\AutoClickerHandler;
use core\game\boop\handler\types\hacks\FlyHandler;
use core\game\boop\handler\types\hacks\InstantBreakHandler;
use core\game\boop\handler\types\hacks\JetpackHandler;
use core\game\boop\handler\types\hacks\NukeHandler;
use core\game\boop\handler\types\PearlHandler;
use core\game\boop\task\CheatLogTask;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class HandlerManager {

    /** @var Nexus */
    private $core;

    /** @var bool */
    private $halted = false;

    /** @var PearlHandler */
    private $pearlHandler;

    /** @var BreakHandler */
    private $breakHandler;

    /** @var InstantBreakHandler */
    private $instantBreakHandler;

    /** @var NukeHandler */
    private $nukeHandler;

    /** @var AttackHandler */
    private $attackHandler;

    /** @var FlyHandler */
    private $flyHandler;

    /** @var AutoClickerHandler */
    private $autoClickerHandler;

    /** @var JetpackHandler */
    private $jetpackHandler;
    /**
     * BOOPManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        //$core->getScheduler()->scheduleRepeatingTask(new TPSCheckTask($core), 20);
        $core->getScheduler()->scheduleRepeatingTask(new ResetViolationsTask($this), 12000);
        $core->getServer()->getPluginManager()->registerEvents(new HandlerListener($core), $core);
        $this->init();
    }

    public function init(): void {
        $this->pearlHandler = new PearlHandler($this->core);
        $this->breakHandler = new BreakHandler($this->core);
        $this->instantBreakHandler = new InstantBreakHandler($this->core);
        $this->nukeHandler = new NukeHandler($this->core);
        $this->attackHandler = new AttackHandler($this->core);
        $this->flyHandler = new FlyHandler($this->core);
        $this->autoClickerHandler = new AutoClickerHandler($this->core);
        $this->jetpackHandler = new JetpackHandler($this->core);
    }

    /**
     * @return PearlHandler
     */
    public function getPearlHandler(): PearlHandler {
        return $this->pearlHandler;
    }

    /**
     * @return BreakHandler
     */
    public function getBreakHandler(): BreakHandler {
        return $this->breakHandler;
    }

    /**
     * @return InstantBreakHandler
     */
    public function getInstantBreakHandler(): InstantBreakHandler {
        return $this->instantBreakHandler;
    }

    /**
     * @return NukeHandler
     */
    public function getNukeHandler(): NukeHandler {
        return $this->nukeHandler;
    }

    /**
     * @return AttackHandler
     */
    public function getAttackHandler(): AttackHandler {
        return $this->attackHandler;
    }

    /**
     * @return FlyHandler
     */
    public function getFlyHandler(): FlyHandler {
        return $this->flyHandler;
    }

    /**
     * @return AutoClickerHandler
     */
    public function getAutoClickerHandler(): AutoClickerHandler {
        return $this->autoClickerHandler;
    }

    /**
     * @return JetpackHandler
     */
    public function getJetpackHandler(): JetpackHandler {
        return $this->jetpackHandler;
    }

    /**
     * @return bool
     */
    public function isHalted(): bool {
        return $this->halted;
    }

    /**
     * @param bool $halted
     */
    public function setHalted(bool $halted): void {
        if($halted) {
            $message = TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "BOOP" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::RED . "Detections have been halted due to low TPS!";
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
            $this->core->getServer()->getAsyncPool()->submitTaskToWorker(new CheatLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()) . TextFormat::clean($message)), 1);
            $this->core->getLogger()->info($message);
        }
        else {
            $message = TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "BOOP" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::GREEN . "Detections are back online!";
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
            $this->core->getServer()->getAsyncPool()->submitTaskToWorker(new CheatLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()) . TextFormat::clean($message)), 1);
            $this->core->getLogger()->info($message);
        }
        $this->halted = $halted;
    }
}
