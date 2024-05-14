<?php

namespace core\game\boop\handler\event;

use pocketmine\player\Player;
use pocketmine\entity\Entity;
use pocketmine\event\Event;
use pocketmine\event\Cancellable;

class PearlThrownEvent extends Event implements Cancellable {

    /** @var Player */
    private $player;

    /** @var Entity */
    private $entity;

    /** @var int */
    private $id;

    /**
     * PearlThrownEvent constructor.
     *
     * @param Player $player
     * @param Entity $entity
     */
    public function __construct(Player $player, Entity $entity) {
        $this->player = $player;
        $this->entity = $entity;
        $this->id = $entity->getId();
    }

    /**
     * Get the player who threw the pearl
     * @return Player
     */
    public function getPlayer(): Player {
        return $this->player;
    }

    /**
     * Get the pearl entity
     * @return Entity
     */
    public function getEntity(): Entity {
        return $this->entity;
    }

    /**
     * Get the entity id, this is here to handle, even if the entity is despawned.
     * @return int
     */
    public function getEntityId(): int {
        return $this->id;
    }
}