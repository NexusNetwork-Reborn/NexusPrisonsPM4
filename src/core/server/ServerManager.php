<?php

namespace core\server;

use core\Nexus;
use core\server\announcement\AnnouncementManager;
use core\server\area\AreaManager;
use core\server\inventory\InventoryTypes;
use Exception;

class ServerManager {

    /** @var Nexus */
    private $core;

    /** @var AnnouncementManager */
    private $announcementManager;

    /** @var AreaManager */
    private $areaManager;

    /**
     * VaultManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        try {
            $this->init();
        }
        catch(Exception $exception) {
            $this->core->getLogger()->error("Failed to load the manager for server!");
            $this->core->getLogger()->logException($exception);
        }
    }

    public function init(): void {
        $this->announcementManager = new AnnouncementManager($this->core);
        $this->areaManager = new AreaManager($this->core);
        InventoryTypes::registerCustomMenuTypes();
    }

    /**
     * @return AnnouncementManager
     */
    public function getAnnouncementManager(): AnnouncementManager {
        return $this->announcementManager;
    }

    /**
     * @return AreaManager
     */
    public function getAreaManager(): AreaManager {
        return $this->areaManager;
    }
}