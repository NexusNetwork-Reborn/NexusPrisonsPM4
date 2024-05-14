<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace core\level\entity\animation;

use core\level\entity\types\FireworksRocket;
use pocketmine\entity\animation\Animation;
use pocketmine\entity\Living;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;

final class FireworkParticlesAnimation implements Animation {

    /** @var Living */
    private $rocket;

    /**
     * ArmSwingAnimation constructor.
     *
     * @param FireworksRocket $rocket
     */
    public function __construct(FireworksRocket $rocket) {
        $this->rocket = $rocket;
    }

    /**
     * @return array
     */
    public function encode(): array {
        return [
            ActorEventPacket::create($this->rocket->getId(), ActorEvent::FIREWORK_PARTICLES, 0)
        ];
    }
}
