<?php

namespace core\game\item\pet;

use core\player\NexusPlayer;

abstract class Pet {

    /** @var int */
    private $name;

    /** @var string */
    private $description;

    /** @var string[] */
    private $abilities;

    /** @var int */
    private $maxLevel;

    /** @var int */
    private $cooldown;

    /**
     * Pet constructor.
     *
     * @param string $name
     * @param string $description
     * @param array $abilities
     * @param int $maxLevel
     * @param int $cooldown
     */
    public function __construct(string $name, string $description, array $abilities, int $maxLevel, int $cooldown) {
        $this->name = $name;
        $this->description = $description;
        $this->abilities = $abilities;
        $this->maxLevel = $maxLevel;
        $this->cooldown = $cooldown;
    }

    /**
     * @return int
     */
    abstract public function getXPIncrement(): int;

    /**
     * @param NexusPlayer $player
     */
    abstract public function onItemUse(NexusPlayer $player): void;

    /**
     * @return string
     */
    abstract public function getColoredName(): string;

    /**
     * @return int
     */
    public function getName(): int|string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @return string[]
     */
    public function getAbilities(): array {
        return $this->abilities;
    }

    /**
     * @return int
     */
    public function getMaxLevel(): int {
        return $this->maxLevel;
    }

    /**
     * @return int
     */
    public function getCooldown(): int {
        return $this->cooldown;
    }
}