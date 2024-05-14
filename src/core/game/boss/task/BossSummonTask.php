<?php

namespace core\game\boss\task;

use core\game\boss\BossFight;
use core\Nexus;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class BossSummonTask extends Task {

    private int $phase = 0;

    private static ?BossFight $bossFight = null;

    private ?int $internalCountdown = 11100;

    public function __construct(int $phase = 0)
    {
        $this->phase = $phase;
        self::$bossFight = new BossFight([], $this);
    }

    public function setPhase(int $phase = 0): BossSummonTask
    {
        $this->phase = $phase;
        if($phase === 0) self::$bossFight = null;
        return $this;
    }

    public function onRun() : void
    {
        $prefix = TextFormat::YELLOW . TextFormat::BOLD . "(!) " . TextFormat::RED . TextFormat::UNDERLINE . "HADES" . TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " /boss " . TextFormat::RESET . TextFormat::GRAY . "- " . TextFormat::RESET . TextFormat::YELLOW . "queue opening in " . TextFormat::UNDERLINE;
        //$prefix = TextFormat::RED . "[" . TextFormat::BOLD . TextFormat::AQUA . "nexus " . TextFormat::GOLD . TextFormat::BOLD . "Bosses" . TextFormat::RESET . TextFormat::RED . "] " . TextFormat::RESET . TextFormat::GREEN;
        switch ($this->phase){
            case 0:
                $this->internalCountdown = 10800;
                Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void {
                    if($this->internalCountdown > 0) {
                        $this->internalCountdown--;
                    }
                    if($this->internalCountdown === 0 && $this->getHandler() !== null) {
                        $this->getHandler()->cancel();
                    }
                }), 20);
                Server::getInstance()->broadcastMessage($prefix . "3h!");
                //Server::getInstance()->broadcastMessage($prefix . "The ground underneath you begins to rumble... The boss will arrive in 3 hours.");
                $this->setHandler(null);
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask($this->setPhase(1), 9300 * 20);
                break;
            case 1:
                Server::getInstance()->broadcastMessage($prefix . "30m!");
                //Server::getInstance()->broadcastMessage($prefix . "The shaking of the underworld intensifies... The boss will arrive in 30 minutes.");
                $this->setHandler(null);
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask($this->setPhase(2), 900 * 20);
                break;
            case 2:
                Server::getInstance()->broadcastMessage($prefix . "10m!");
                //Server::getInstance()->broadcastMessage($prefix . "You sense the evil rising up from beneath you... The boss will arrive in 15 minutes.");
                $this->setHandler(null);
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask($this->setPhase(3), 600 * 20);
                Nexus::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function() use ($prefix): void {
                    if($this->iter > 0) {
                        Server::getInstance()->broadcastMessage($prefix . $this->iter . "s!");
                        $this->iter--;
                    } else if(!$this->queueOpen) {
                        $this->queueOpen = true;
                        $this->internalCountdown = 60;
                        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function(): void {
                            $this->internalCountdown--;
                            if($this->internalCountdown === 0) {
                                $this->getHandler()->cancel();
                            }
                        }), 20);
                        $pref = TextFormat::YELLOW . TextFormat::BOLD . "(!) QUEUE UP FOR " . TextFormat::RED . TextFormat::UNDERLINE . "HADES" . TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " /boss " . TextFormat::RESET . TextFormat::GRAY . "- " . TextFormat::RESET . TextFormat::YELLOW . "starting in " . TextFormat::UNDERLINE;
                        Server::getInstance()->broadcastMessage($pref . "60s!");
                        $this->getHandler()?->cancel();
                    }
                }), 595 * 20, 20);
                break;
            case 3:
                if(self::$bossFight === null){
                    self::$bossFight = new BossFight([], $this);
                }
                self::$bossFight->openForJoining();
                //Server::getInstance()->broadcastMessage($prefix . "Hell itself rises up against our world: Hades, Keeper Of The Underworld, will arrive in 5 minutes. Use /boss to take a stand against this ancient evil!");
                $this->setHandler(null);
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask($this->setPhase(4), 60 * 20);
                break;
            case 4:
                $this->setHandler(null);
                self::$bossFight->closeJoining();
                self::$bossFight->start($this);
                Server::getInstance()->broadcastMessage(TextFormat::YELLOW . TextFormat::BOLD . "(!) " . TextFormat::RED . TextFormat::UNDERLINE . "HADES" . TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " has arrived!");
                //Server::getInstance()->broadcastMessage($prefix . "The underworld has risen to challenge our " . Nexus::PLANET . " and you, fellow players!");
                // TODO: Decide if this message is to be kept or not.
                //Server::getInstance()->broadcastMessage(TextFormat::RED . TextFormat::BOLD . "[Hades] " . TextFormat::RESET . TextFormat::GOLD . "I have arrived mortals, prepare your souls, for they are now mine!");
                break;
        }
    }

    private $queueOpen = false;

    private $iter = 5;

    public static function initiatePhase(int $phase = 3) {
        $task = self::getBossFight()->getSummonTask();
        $task->getHandler()?->cancel();
        $task->phase = $phase;
        $task->onRun();
    }

    public static function getBossFight(): ?BossFight
    {
        return self::$bossFight;
    }

    public function getTimeToStart(): string
    {
        if($this->internalCountdown <= 60) {
            return $this->internalCountdown . "s";
        }
        $h = intval($this->internalCountdown / 3600);
        $m = intval(($this->internalCountdown % 3600) / 60);
        return "{$h}h, {$m}m";
    }

}