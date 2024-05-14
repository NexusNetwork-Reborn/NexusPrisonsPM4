<?php
declare(strict_types=1);

namespace core\server\announcement\task;

use core\Nexus;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class BroadcastMessagesTask extends Task {

    /** @var Nexus */
    private $core;

    /**
     * BroadcastMessagesTask constructor.
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
        $message = $this->core->getServerManager()->getAnnouncementManager()->getNextMessage();
        $this->core->getServer()->broadcastMessage(TextFormat::DARK_GRAY . "[" . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Announcement" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::YELLOW . $message);
    }
}