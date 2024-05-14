<?php

namespace core\display;

use core\display\animation\AnimationManager;
use core\Nexus;

class DisplayManager {

    /** @var Nexus */
    private $core;

    /** @var AnimationManager */
    private $animationManager;

    /**
     * DisplayManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
    }

    public function init(): void {
        $this->animationManager = new AnimationManager($this->core);
    }

    /**
     * @return AnimationManager
     */
    public function getAnimationManager(): AnimationManager {
        return $this->animationManager;
    }
}