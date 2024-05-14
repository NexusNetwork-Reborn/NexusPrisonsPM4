<?php
declare(strict_types=1);

namespace core\level\entity;

use core\display\animation\entity\AnimationEntity;
use core\game\badlands\bandit\BanditBoss;
use core\game\badlands\bandit\EliteBandit;
use core\game\badlands\bandit\XPBandit;
use core\Nexus;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\Listener;
use pocketmine\item\ItemIds;

class EntityListener implements Listener {

    /** @var Nexus */
    private $core;

    /** @var Entity[] */
    private $entities = [];

    /** @var string[] */
    private $ids = [];

    /**
     * NexusListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority HIGHEST
     *
     * @param EntitySpawnEvent $event
     */
    public function onEntitySpawn(EntitySpawnEvent $event) {
        $entity = $event->getEntity();
        if($entity instanceof ExperienceOrb) {
            $entity->flagForDespawn();
            return;
        }
        if($entity instanceof ItemEntity and $entity->getItem()->getId() === ItemIds::DOUBLE_PLANT) {
            $entity->flagForDespawn();
            return;
        }
        if($entity instanceof Human) {
            return;
        }
        $uuid = md5(microtime());
        if($entity instanceof ItemEntity and (!$entity instanceof AnimationEntity)) {
            if(count($this->entities) > 350) {
                $despawn = array_shift($this->entities);
                if(!$despawn->isClosed()) {
                    $despawn->flagForDespawn();
                }
            }
            $item = $entity->getItem();
            $entity->setNameTag($item->hasCustomName() ? $item->getCustomName() : $item->getName());
            $entity->setNameTagVisible();
            $entity->setNameTagAlwaysVisible();
            $this->ids[$entity->getId()] = $uuid;
            $this->entities[$uuid] = $entity;
        }
    }

    /**
     * @priority HIGHEST
     *
     * @param EntityDespawnEvent $event
     */
    public function onEntityDespawn(EntityDespawnEvent $event): void {
        $entity = $event->getEntity();
        if($entity instanceof XPBandit || $entity instanceof EliteBandit || $entity instanceof BanditBoss) return;
        if(!isset($this->ids[$entity->getId()])) {
            return;
        }
        $uuid = $this->ids[$entity->getId()];
        unset($this->ids[$entity->getId()]);
        if(isset($this->entities[$uuid])) {
            unset($this->entities[$uuid]);
        }
    }
}