<?php
declare(strict_types=1);

namespace core\game\plots\task;

use core\game\plots\PlotManager;
use core\Nexus;
use libs\utils\Task;

class UpdatePlotsTask extends Task {

    /** @var PlotManager */
    private $manager;

    /**
     * UpdatePlotsTask constructor.
     *
     * @param PlotManager $manager
     */
    public function __construct(PlotManager $manager) {
        $this->manager = $manager;
    }

    public function onRun(): void {
        $count = 0;
        foreach($this->manager->getPlots() as $plot) {
            $owner = $plot->getOwner();
            if($owner !== null) {
                if($owner->needsUpdate()) {
                    $owner->updateAsync();
                    ++$count;
                }
            }
        }
        Nexus::getInstance()->getLogger()->notice("[Auto Save] Successfully saved $count plots!");
    }
}