<?php

declare(strict_types = 1);

namespace core\game\badlands\bandit\task;

use core\game\badlands\BadlandsManager;
use core\game\badlands\bandit\XPBandit;
use pocketmine\block\Liquid;
use core\game\zone\BadlandsRegion;
use core\Nexus;
use pocketmine\entity\Location;
use pocketmine\scheduler\Task;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\block\Air;

class XPBanditSpawnTask extends Task
{

    public function onRun(): void
    {
        foreach(Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlands() as $badland) {
            if (count($badland->getBanditsInside()) >= BadlandsManager::WORLD_CAP) return;
            $pos = $badland->generateRandomPos();
            $entity = (new XPBandit($pos, CompoundTag::create(), $badland));
            $entity->region = $badland;
            $entity->spawnToAll();
        }
    }
}