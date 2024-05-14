<?php
declare(strict_types=1);

namespace core\level\block;

interface Ore {

    /**
     * @return int
     */
    public function getXPDrop(): int;

    /**
     * @return int
     */
    public function getEnergyDrop(): int;
}