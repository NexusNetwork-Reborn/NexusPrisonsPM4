<?php
declare(strict_types=1);

namespace core\player\gang\task;

use core\Nexus;
use core\player\gang\GangManager;
use libs\utils\Task;

class UpdateGangsTask extends Task {

    /** @var GangManager */
    private $manager;

    /**
     * UpdateGangsTask constructor.
     *
     * @param GangManager $manager
     */
    public function __construct(GangManager $manager) {
        $this->manager = $manager;
    }

    public function onRun(): void {
        $count = 0;
        foreach($this->manager->getGangs() as $gang) {
            if($gang->needsUpdate()) {
                $gang->updateAsync();
                ++$count;
            }
        }
        Nexus::getInstance()->getLogger()->notice("[Auto Save] Successfully saved $count gangs!");
    }
}