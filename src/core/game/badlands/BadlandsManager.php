<?php

declare(strict_types = 1);

namespace core\game\badlands;

use core\game\badlands\bandit\BanditBoss;
use core\game\badlands\bandit\EliteBandit;
use core\game\badlands\bandit\task\EliteBanditSpawnTask;
use core\game\badlands\bandit\task\XPBanditSpawnTask;
use core\game\badlands\bandit\XPBandit;
use core\Nexus;
use pocketmine\entity\EntityDataHelper;
use pocketmine\world\World;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\EntityFactory;

class BadlandsManager
{

    public const WORLD_CAP = 50;

    public function __construct()
    {
        Nexus::getInstance()->getServer()->getPluginManager()->registerEvents(new BadlandsListener(), Nexus::getInstance());
        $this->init();
    }

    public function init() : void
    {
        EntityFactory::getInstance()->register(XPBandit::class, function(World $world, CompoundTag $nbt) : XPBandit {
            return new XPBandit(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["XPBandit"]);
        EntityFactory::getInstance()->register(EliteBandit::class, function(World $world, CompoundTag $nbt) : EliteBandit {
            return new EliteBandit(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["EliteBandit"]);
        EntityFactory::getInstance()->register(BanditBoss::class, function(World $world, CompoundTag $nbt) : BanditBoss {
            return new BanditBoss(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["BanditBoss"]);

       # Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new XPBanditSpawnTask(), 300 * 20);
       # Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new EliteBanditSpawnTask(), 450 * 20);
    }
}