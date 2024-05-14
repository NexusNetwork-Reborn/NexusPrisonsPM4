<?php

declare(strict_types = 1);

namespace core\game\item\enchantment;

use core\game\item\types\custom\Energy;
use core\player\NexusPlayer;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

abstract class Enchantment extends \pocketmine\item\enchantment\Enchantment implements EnchantmentIdentifiers {

    const SIMPLE = 0;
    const UNCOMMON = 1;
    const ELITE = 2;
    const ULTIMATE = 3;
    const LEGENDARY = 4;
    const GODLY = 5;
    const EXECUTIVE = 6;
    const ENERGY = 7;

    const DAMAGE = 0;
    const BREAK = 1;
    const EFFECT_ADD = 2;
    const MOVE = 3;
    const DEATH = 4;
    const SHOOT = 5;
    const INTERACT = 6;
    const DAMAGE_BY = 7;
    const DAMAGE_BY_ALL = 8;
    const KILL = 9;

    public const SLOT_NONE = 0x0;
    public const SLOT_ALL = 0xffff;
    public const SLOT_ARMOR = self::SLOT_HEAD | self::SLOT_TORSO | self::SLOT_LEGS | self::SLOT_FEET;
    public const SLOT_HEAD = 0x1;
    public const SLOT_TORSO = 0x2;
    public const SLOT_LEGS = 0x4;
    public const SLOT_FEET = 0x8;
    public const SLOT_SWORD = 0x10;
    public const SLOT_BOW = 0x20;
    public const SLOT_SATCHEL = 0x50;
    public const SLOT_TOOL = self::SLOT_HOE | self::SLOT_SHEARS | self::SLOT_FLINT_AND_STEEL;
    public const SLOT_HOE = 0x40;
    public const SLOT_SHEARS = 0x80;
    public const SLOT_FLINT_AND_STEEL = 0x100;
    public const SLOT_DIG = self::SLOT_AXE | self::SLOT_PICKAXE | self::SLOT_SHOVEL;
    public const SLOT_AXE = 0x200;
    public const SLOT_PICKAXE = 0x400;
    public const SLOT_SHOVEL = 0x800;
    public const SLOT_FISHING_ROD = 0x1000;
    public const SLOT_CARROT_STICK = 0x2000;
    public const SLOT_ELYTRA = 0x4000;
    public const SLOT_TRIDENT = 0x8000;

    /** @var int */
    private $id;

    /** @var callable */
    protected $callable;

    /** @var string */
    private $description;

    /** @var int */
    private $eventType;

    /** @var int|null */
    private $premature;

    /**
     * Enchantment constructor.
     *
     * @param int $id
     * @param string $name
     * @param int $rarity
     * @param string $description
     * @param int $eventType
     * @param int $flag
     * @param int $maxLevel
     * @param int $secondaryFlag
     * @param int|null $premature
     */
    public function __construct(int $id, string $name, int $rarity, string $description, int $eventType, int $flag, int $maxLevel, int $secondaryFlag = self::SLOT_NONE, ?int $premature = null) {
        $this->id = $id;
        $this->description = $description;
        $this->eventType = $eventType;
        $this->premature = $premature;
        parent::__construct($name, $rarity, $flag, $secondaryFlag, $maxLevel);
    }

    /**
     * @return int
     */
    public function getRuntimeId(): int {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getEventType(): int {
        return $this->eventType;
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @return callable
     */
    public function getCallable(): callable {
        return $this->callable;
    }

    /**
     * @return int|null
     */
    public function getPremature(): ?int {
        return $this->premature;
    }
}