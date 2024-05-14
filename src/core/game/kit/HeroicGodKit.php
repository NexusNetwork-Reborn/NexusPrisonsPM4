<?php

declare(strict_types = 1);

namespace core\game\kit;

use core\player\NexusPlayer;

abstract class HeroicGodKit extends GodKit {

    /**
     * GodKit constructor.
     *
     * @param int $maxTier
     * @param string $name
     * @param string $color
     * @param int $cooldown
     */
    public function __construct(string $name, string $color) {
        parent::__construct($name, $color, 259200);
    }

    /**
     * @param NexusPlayer $player
     *
     * @return int
     */
    public function getCooldownFor(NexusPlayer $player): int {
        return $this->cooldown;
    }
}