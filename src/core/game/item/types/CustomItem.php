<?php
declare(strict_types=1);

namespace core\game\item\types;

use core\Nexus;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;

abstract class CustomItem {

    const CUSTOM = "custom";

    const ITEM_CLASS = "ItemClass";

    const UUID = "UniqueId";

    /** @var string */
    private $name;

    /** @var string[] */
    private $lore;

    /** @var Item */
    private $item;

    /** @var bool */
    private $enchanted;

    /** @var bool */
    private $unique;

    /** @var null|string */
    private $uniqueId = null;

    /**
     * CustomItem constructor.
     *
     * @param Item $item
     * @param string $customName
     * @param array $lore
     * @param bool $enchanted
     * @param bool $unique
     * @param string|null $uniqueId
     */
    public function __construct(Item $item, string $customName, array $lore = [], bool $enchanted = false, bool $unique = false, ?string $uniqueId = null) {
        $this->item = $item;
        $this->name = $customName;
        $this->lore = $lore;
        $this->enchanted = $enchanted;
        $this->unique = $unique;
        if($this->unique) {
            if($uniqueId === null) {
                $this->uniqueId = md5(microtime());
            }
            else {
                $this->uniqueId = $uniqueId;
            }
        }
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * @param Item $item
     *
     * @return mixed
     */
    abstract public static function fromItem(Item $item);

    /**
     * @return CompoundTag
     */
    abstract public function dataToCompoundTag(): CompoundTag;

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [];
    }

    /**
     * @param Item $item
     *
     * @return CompoundTag|null
     */
    public static function getCustomTag(Item $item): ?CompoundTag {
        $tag = $item->getNamedTag();
        return $tag->getCompoundTag(self::CUSTOM);
    }

    public function generateNewUniqueId(): void {
        $this->uniqueId = md5(microtime());
    }

    /**
     * @return $this
     */
    public function createNewItem(): self {
        if($this->unique) {
            $this->uniqueId = md5(microtime());
        }
        return $this;
    }

    /**
     * @return Item
     */
    public function toItem(): Item {
        $item = $this->item;
        $compoundTag = $this->dataToCompoundTag();
        if(!$compoundTag->getTag(self::ITEM_CLASS) instanceof StringTag) {
            $compoundTag->setString(self::ITEM_CLASS, get_class($this));
        }
        if($this->unique) {
            if($this->uniqueId !== null) {
                $compoundTag->setString(self::UUID, $this->uniqueId);
            }
            else {
                $compoundTag->setString(self::UUID, md5(microtime()));
            }
        }
        $item->setNamedTag($item->getNamedTag()->setTag(self::CUSTOM, $compoundTag));
        if($this->enchanted) {
            $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
        }
        $item->setCustomName($this->getName());
        $item->setLore($this->getLore());
        return $item->setCount(1);
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public static function isInstanceOf(Item $item): bool {
        $compound = self::getCustomTag($item);
        if($compound === null) {
            return false;
        }
        if($compound->getTag(self::ITEM_CLASS) instanceof StringTag) {
            $className = $compound->getString(self::ITEM_CLASS);
            if($className === get_called_class()) {
                return true;
            }
            return false;
        }
        else {
            $requiredTags = self::getRequiredTags();
            foreach($requiredTags as $name => $type) {
                if(!$compound->getTag($name) instanceof $type) {
                    return false;
                }
            }
            $compound->setString(self::ITEM_CLASS, get_called_class());
            $item->setNamedTag($compound);
        }
        return true;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getLore(): array {
        return $this->lore;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    public function setUsed(): void {
        Nexus::getInstance()->getGameManager()->getItemManager()->setRedeemed($this->uniqueId);
    }

    /**
     * @return string|null
     */
    public function getUniqueId(): ?string {
        return $this->uniqueId;
    }
}