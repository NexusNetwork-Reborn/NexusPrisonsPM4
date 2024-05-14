<?php
declare(strict_types=1);

namespace core\provider\task;

use core\Nexus;
use core\provider\thread\MySQLThread;
use pocketmine\scheduler\Task;

class ReadResultsTask extends Task {

    /** @var MySQLThread */
    private $thread;

    /**
     * ReadResultsTask constructor.
     *
     * @param MySQLThread $thread
     */
    public function __construct(MySQLThread $thread) {
        $this->thread = $thread;
    }

    /**
     * @param int $currentTick
     *
     */
    public function onRun(): void {
        if(!$this->thread->isRunning()) {
            $this->thread = Nexus::getInstance()->getMySQLProvider()->createNewThread();
        }
        $this->thread->checkResults();
    }
}
