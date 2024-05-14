<?php

declare(strict_types = 1);

namespace core\game\gamble\task;

use core\game\gamble\GambleManager;
use core\game\item\types\custom\Energy;
use core\Nexus;
use libs\utils\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class DrawLotteryTask extends Task {

    /** @var GambleManager */
    private $manager;

    /** @var int */
    private $time = 14400;

    /**
     * DrawLotteryTask constructor.
     *
     * @param GambleManager $manager
     */
    public function __construct(GambleManager $manager) {
        $this->manager = $manager;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        $hours = floor($this->time / 3600);
        $minutes = floor(floor(($this->time / 60)) % 60);
        $seconds = $this->time % 60;
        $total = $this->manager->getTotalDraws() * GambleManager::TICKET_PRICE;
        $total *= 0.9;
        $total = (int)floor($total);
        if($hours < 1) {
            if(($minutes <= 10 and $seconds == 0) or ($minutes == 0 and $seconds <= 10)) {
                Server::getInstance()->broadcastMessage(TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "Lottery" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::YELLOW . "Drawing " . TextFormat::AQUA . number_format($total) . " Energy" . TextFormat::YELLOW . " in $hours hours, $minutes minutes, and $seconds seconds.");
            }
            if($minutes <= 0 and $seconds <= 0) {
                $winner = $this->manager->draw();
                if($winner === null) {
                    Server::getInstance()->broadcastMessage(TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "Lottery" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::YELLOW . "There are no winners!");
                }
                else {
                    $online = Server::getInstance()->getPlayerByPrefix($winner);
                    if($online !== null) {
                        if($online->getInventory()->firstEmpty() !== -1) {
                            $online->getInventory()->addItem((new Energy($total))->toItem());
                        }
                        else {
                            $online->getWorld()->dropItem($online->getPosition(), (new Energy($total))->toItem());
                        }
                    }
                    else {
                        $database = Nexus::getInstance()->getMySQLProvider()->getDatabase();
                        $stmt = $database->prepare("SELECT inbox FROM players WHERE username = ?;");
                        $stmt->bind_param("s", $winner);
                        $stmt->execute();
                        $stmt->bind_result($inbox);
                        $stmt->fetch();
                        $stmt->close();
                        $items = [];
                        if($inbox !== null) {
                            $items = Nexus::decodeInventory($inbox);
                        }
                        $items[] = (new Energy($total))->toItem();
                        $inbox = Nexus::encodeItems($items);
                        $stmt = $database->prepare("UPDATE players SET inbox = ? WHERE username = ?;");
                        $stmt->bind_param("ss", $inbox, $winner);
                        $stmt->execute();
                        $stmt->close();
                    }
                    $tickets = $this->manager->getPot()[$winner];
                    $percentage = $tickets / $this->manager->getTotalDraws();
                    $percentage = round($percentage * 100, 2);
                    Server::getInstance()->broadcastMessage(TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "Lottery" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::YELLOW . "Congratulations to $winner for winning " . TextFormat::AQUA . number_format($total) . " Energy" . TextFormat::YELLOW . "! This user has bought $tickets tickets($percentage%)!");
                }
                if(Nexus::getInstance()->getServerManager()->getAnnouncementManager()->getRestarter()->getRestartProgress() > 3600) {
                    $this->time = 14400;
                    $this->manager->resetPot();
                }
                else {
                    $this->cancel();
                }
            }
        }
        $this->time--;
    }

    /**
     * @return int
     */
    public function getTimeLeft(): int {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime(int $time): void {
        $this->time = $time;
    }
}
