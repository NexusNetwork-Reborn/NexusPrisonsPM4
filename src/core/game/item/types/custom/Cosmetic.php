<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

class Cosmetic {

    /** @var string */
    private $name;

    /** @var string[] */
    private $lore;

    /** @var Item */
    private $item;

    /** @var bool */
    private $enchanted;

    /**
     * Cosmetic constructor.
     *
     * @param Item $item
     * @param string $customName
     * @param array $lore
     * @param bool $enchanted
     */
    public function __construct(Item $item, string $customName, array $lore = [], bool $enchanted = false) {
        $this->item = $item;
        $this->name = $customName;
        $this->lore = $lore;
        $this->enchanted = $enchanted;
    }

    /**
     * @return Item
     */
    public function toItem(): Item {
        $item = $this->item;
        if($this->enchanted) {
            $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
        }
        $item->setCustomName($this->name);
        $item->setLore($this->lore);
        return $item->setCount(1);
    }
}