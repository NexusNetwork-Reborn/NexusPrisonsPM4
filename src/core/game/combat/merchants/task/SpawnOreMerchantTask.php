<?php
declare(strict_types=1);

namespace core\game\combat\merchants\task;

use core\game\combat\CombatManager;
use core\Nexus;
use libs\utils\Task;
use pocketmine\Server;

class SpawnOreMerchantTask extends Task {

    /** @var CombatManager */
    private $manager;

    /** @var int */
    private $spawnRate = 0;

    /** @var int */
    private $resetRate = 3600;

    /**
     * SpawnOreMerchantTask constructor.
     *
     * @param CombatManager $manager
     */
    public function __construct(CombatManager $manager) {
        $this->manager = $manager;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        if(count(Server::getInstance()->getOnlinePlayers()) <= 0) {
            return;
        }
        if(Nexus::getInstance()->getServerManager()->getAnnouncementManager()->getRestarter()->getRestartProgress() > 5) {
            if(--$this->spawnRate <= 0) {
                $this->manager->spawnMerchantShop();
                $this->spawnRate = 1200;
            }
            if(--$this->resetRate <= 0) {
                $this->manager->resetMerchantShops();
                $this->resetRate = 3600;
            }
        }
    }

    /**
     * @return int
     */
    public function getTimeLeft(): int {
        return $this->spawnRate;
    }
}
