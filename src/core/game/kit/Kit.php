<?php

declare(strict_types = 1);

namespace core\game\kit;

use core\player\NexusPlayer;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

abstract class Kit {

	/** @var string */
	protected $name;

	/** @var int */
    protected $cooldown;

    /** @var string */
    protected $color;

    /**
     * Kit constructor.
     *
     * @param string $name
     * @param string $color
     * @param int $cooldown
     */
	public function __construct(string $name, string $color, int $cooldown) {
		$this->name = $name;
		$this->color = $color;
		$this->cooldown = $cooldown;
	}

    /**
     * @param NexusPlayer $player
     *
     * @return Item[]
     */
    abstract public function giveTo(NexusPlayer $player, bool $give = true): array;

    /**
     * @return string
     */
	public function getName(): string {
		return $this->name;
	}

    /**
     * @return string
     */
    public function getColor(): string {
        return $this->color;
    }

    /**
     * @param NexusPlayer|null $player
     *
     * @return int
     */
	public function getCooldown(?NexusPlayer $player = null): int {
	    return $this->cooldown;
    }

    /**
     * @param int $level
     *
     * @return Item[]
     */
    protected function levelToArmorSet(int $level): array {
        if($level >= 100) {
            return [
                ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD, 0, 1)
            ];
        }
        elseif($level >= 60) {
            return [
                ItemFactory::getInstance()->get(ItemIds::IRON_HELMET, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::IRON_CHESTPLATE, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::IRON_LEGGINGS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::IRON_BOOTS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::IRON_SWORD, 0, 1)
            ];
        }
        elseif($level >= 30) {
            return [
                ItemFactory::getInstance()->get(ItemIds::GOLDEN_HELMET, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::GOLDEN_CHESTPLATE, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::GOLDEN_LEGGINGS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::GOLDEN_BOOTS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::GOLDEN_SWORD, 0, 1)
            ];
        }
        elseif($level >= 10) {
            return [
                ItemFactory::getInstance()->get(ItemIds::CHAIN_HELMET, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_CHESTPLATE, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_LEGGINGS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_BOOTS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::STONE_SWORD, 0, 1)
            ];
        }
        else {
            return [
                ItemFactory::getInstance()->get(ItemIds::CHAIN_HELMET, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_CHESTPLATE, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_LEGGINGS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_BOOTS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::WOODEN_SWORD, 0, 1)
            ];
        }
    }

    /**
     * @param int $level
     *
     * @return Item[]
     */
    protected function levelToArmorSetAxe(int $level): array {
        if($level >= 100) {
            return [
                ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::DIAMOND_AXE, 0, 1)
            ];
        }
        elseif($level >= 60) {
            return [
                ItemFactory::getInstance()->get(ItemIds::IRON_HELMET, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::IRON_CHESTPLATE, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::IRON_LEGGINGS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::IRON_BOOTS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::IRON_AXE, 0, 1)
            ];
        }
        elseif($level >= 30) {
            return [
                ItemFactory::getInstance()->get(ItemIds::GOLDEN_HELMET, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::GOLDEN_CHESTPLATE, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::GOLDEN_LEGGINGS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::GOLDEN_BOOTS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::GOLDEN_AXE, 0, 1)
            ];
        }
        elseif($level >= 10) {
            return [
                ItemFactory::getInstance()->get(ItemIds::CHAIN_HELMET, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_CHESTPLATE, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_LEGGINGS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_BOOTS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::STONE_AXE, 0, 1)
            ];
        }
        else {
            return [
                ItemFactory::getInstance()->get(ItemIds::CHAIN_HELMET, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_CHESTPLATE, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_LEGGINGS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::CHAIN_BOOTS, 0, 1),
                ItemFactory::getInstance()->get(ItemIds::WOODEN_AXE, 0, 1)
            ];
        }
    }

    /**
     * @param int $level
     *
     * @return Item
     */
    protected function levelToPickaxe(int $level): Item {
        if($level >= 90) {
            return ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE, 0, 1);
        }
        elseif($level >= 70) {
            return ItemFactory::getInstance()->get(ItemIds::IRON_PICKAXE, 0, 1);
        }
        elseif($level >= 50) {
            return ItemFactory::getInstance()->get(ItemIds::GOLD_PICKAXE, 0, 1);
        }
        elseif($level >= 30) {
            return ItemFactory::getInstance()->get(ItemIds::STONE_PICKAXE, 0, 1);
        }
        else {
            return ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE, 0, 1);
        }
    }

    /**
     * @return string
     */
    abstract public function getColoredName(): string;
}