<?php

namespace core\game\wormhole\task;

use core\display\animation\entity\WormholeSelectEntity;
use core\game\wormhole\WormholeSession;
use core\level\LevelManager;
use libs\utils\Task;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\world\Position;

class SpawnOptionsTask extends Task {

    /** @var int */
    private $runs = 0;

    /** @var WormholeSession */
    private $session;

    /** @var EnchantmentInstance[] */
    private $enchantments;

    /** @var WormholeSelectEntity[] */
    private $entities = [];

    /** @var bool */
    private $finish = false;

    /** @var int */
    private $direction;

    /**
     * SpawnOptionsTask constructor.
     *
     * @param WormholeSession $session
     * @param int $yaw
     */
    public function __construct(WormholeSession $session, int $yaw) {
        $this->session = $session;
        $this->enchantments = $session->getEnchantments();
    }

    public function onRun(): void {
        $owner = $this->session->getOwner();
        if($owner->isOnline() === false) {
            $this->cancel();
            return;
        }
        if(empty($this->enchantments)) {
            $this->finish = true;
            $this->session->setEntities($this->entities);
            $this->cancel();
            return;
        }
        $center = $this->session->getWormhole()->getCenter();
        if($this->runs === 0) {
            $firstOption = array_shift($this->enchantments);
            if(!empty($this->enchantments)) {
                $secondOption = array_shift($this->enchantments);
            }
            $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.first-run.start1"), $center->getWorld())->asVector3();
            $entity = WormholeSelectEntity::createInteractive($owner, Position::fromObject($center->addVector($p), $center->getWorld()), $firstOption, $this->session->getChance($firstOption));
            $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.first-run.dest1"), $center->getWorld())->asVector3();
            $entity->initialize(Position::fromObject($center->addVector($p), $center->getWorld()), 1, false);
            $entity->spawnTo($owner);
            $this->entities[$entity->getId()] = $entity;
            if(isset($secondOption)) {
                $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.first-run.start2"), $center->getWorld())->asVector3();
                $entity = WormholeSelectEntity::createInteractive($owner, Position::fromObject($center->addVector($p), $center->getWorld()), $secondOption, $this->session->getChance($secondOption));
                $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.first-run.dest2"), $center->getWorld())->asVector3();
                $entity->initialize(Position::fromObject($center->addVector($p), $center->getWorld()), 1, false);
                $entity->spawnTo($owner);
                $this->entities[$entity->getId()] = $entity;
            }
        }
        if($this->runs === 1) {
            $firstOption = array_shift($this->enchantments);
            if(!empty($this->enchantments)) {
                $secondOption = array_shift($this->enchantments);
            }
            $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.second-run.start1"), $center->getWorld())->asVector3();
            $entity = WormholeSelectEntity::createInteractive($owner, Position::fromObject($center->addVector($p), $center->getWorld()), $firstOption, $this->session->getChance($firstOption));
            $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.second-run.dest1"), $center->getWorld())->asVector3();
            $entity->initialize(Position::fromObject($center->addVector($p), $center->getWorld()), 1, false);
            $entity->spawnTo($owner);
            $this->entities[$entity->getId()] = $entity;
            if(isset($secondOption)) {
                $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.second-run.start2"), $center->getWorld())->asVector3();
                $entity = WormholeSelectEntity::createInteractive($owner, Position::fromObject($center->addVector($p), $center->getWorld()), $secondOption, $this->session->getChance($secondOption));
                $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.second-run.dest2"), $center->getWorld())->asVector3();
                $entity->initialize(Position::fromObject($center->addVector($p), $center->getWorld()), 1, false);
                $entity->spawnTo($owner);
                $this->entities[$entity->getId()] = $entity;
            }
        }
        if($this->runs === 2) {
            $firstOption = array_shift($this->enchantments);
            if(!empty($this->enchantments)) {
                $secondOption = array_shift($this->enchantments);
            }
            $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.third-run.start1"), $center->getWorld())->asVector3();
            $entity = WormholeSelectEntity::createInteractive($owner, Position::fromObject($center->addVector($p), $center->getWorld()), $firstOption, $this->session->getChance($firstOption));
            $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.third-run.dest1"), $center->getWorld())->asVector3();
            $entity->initialize(Position::fromObject($center->addVector($p), $center->getWorld()), 1, false);
            $entity->spawnTo($owner);
            $this->entities[$entity->getId()] = $entity;
            if(isset($secondOption)) {
                $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.third-run.start2"), $center->getWorld())->asVector3();
                $entity = WormholeSelectEntity::createInteractive($owner, Position::fromObject($center->addVector($p), $center->getWorld()), $secondOption, $this->session->getChance($secondOption));
                $p = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("wormhole.contents.third-run.dest2"), $center->getWorld())->asVector3();
                $entity->initialize(Position::fromObject($center->addVector($p), $center->getWorld()), 1, false);
                $entity->spawnTo($owner);
                $this->entities[$entity->getId()] = $entity;
            }
        }
        $this->runs++;
    }

    public function onCancel(): void {
        if($this->finish === true) {
            return;
        }
        foreach($this->entities as $entity) {
            if(!$entity->isFlaggedForDespawn()) {
                $entity->flagForDespawn();
            }
        }
    }
}