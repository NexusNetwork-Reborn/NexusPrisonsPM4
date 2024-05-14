<?php

declare(strict_types = 1);

namespace core\game\item\sets;

use core\game\item\enchantment\EnchantmentManager;
use pocketmine\block\inventory\FurnaceInventory;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

abstract class Set
{

    /** @var int */
    private int $levelRequirement = 100;

    /**
     * @param int $levelRequirement
     */
    public function __construct(int $levelRequirement = 100)
    {
        $this->levelRequirement = $levelRequirement;
    }

    /**
     * @return int
     */
    public function getLevelRequirement() : int
    {
        return $this->levelRequirement;
    }

    /**
     * @return Item[]
     */
    public function compileSet() : array
    {
        $set = [];

        foreach ($this->getArmor() as $armor) {
            $armor->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
            $set[] = $armor;
        }

        return array_merge($set, [$this->getHandItem()]);
    }

    /** @return Item[] */
    abstract public function getArmor() : array;

    /** @return Item */
    abstract public function getHandItem() : Item;

    /** @return string */
    abstract public function getName() : string;

    /** @return string */
    abstract public function getColor() : string;
}