<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\Interactive;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class Satchel extends Interactive {

    const SATCHEL = "Satchel";

    const ENERGY = "Energy";

    const AMOUNT = "Amount";

    const ID = "Id";

    const DAMAGE = "Damage";

    const WHITESCROLLED = "Whitescrolled";

    /** @var Item */
    private $type;

    /** @var int */
    private $amount;

    /** @var int */
    private $energy;

    /** @var bool */
    private $whitescroll;

    /** @var EnchantmentInstance[] */
    private $enchantments;

    /**
     * Satchel constructor.
     *
     * @param Item $type
     * @param int $amount
     * @param int $energy
     * @param bool $whitescrolled
     * @param array $enchantments
     */
    public function __construct(Item $type, int $amount = 0, int $energy = 0, bool $whitescrolled = false, array $enchantments = []) {
        $name = $type->getName();
        switch($type->getId()) {
            case ItemIds::EMERALD:
            case ItemIds::EMERALD_ORE:
                $color = TextFormat::GREEN;
                break;
            case ItemIds::DIAMOND:
            case ItemIds::DIAMOND_ORE:
                $color = TextFormat::AQUA;
                break;
            case ItemIds::GOLD_INGOT:
            case ItemIds::GOLD_ORE:
                $color = TextFormat::YELLOW;
                break;
            case ItemIds::REDSTONE_DUST:
            case ItemIds::REDSTONE_ORE:
                $color = TextFormat::RED;
                break;
            case ItemIds::DYE:
                $name = "Lapis Lazuli";
                $color = TextFormat::DARK_BLUE;
                break;
            case ItemIds::LAPIS_ORE:
                $color = TextFormat::DARK_BLUE;
                break;
            case ItemIds::IRON_INGOT:
            case ItemIds::IRON_ORE:
                $color = TextFormat::GRAY;
                break;
            case ItemIds::COAL:
            case ItemIds::COAL_ORE:
                $color = TextFormat::DARK_GRAY;
                break;
            default:
                $color = TextFormat::LIGHT_PURPLE;

        }
        $level = XPUtils::xpToLevel($energy, RPGManager::SATCHEL_MODIFIER);
        $max = ($level + 1) * 2304;
        $customName = TextFormat::RESET . TextFormat::BOLD . $color . $name . " Satchel " . TextFormat::RESET . TextFormat::GRAY . "(" . TextFormat::GREEN . number_format($amount) . TextFormat::GRAY . " / " . TextFormat::WHITE . number_format($max) . TextFormat::GRAY . ")";
        if($level > 0) {
            $customName .= TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . " $level";
        }
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . number_format($amount) . TextFormat::GRAY . " / " . TextFormat::WHITE . number_format($max);
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Tool Level " . TextFormat::BOLD . $level;
        $lore[] = TextFormat::RESET . TextFormat::RED . "Needs to be level " . TextFormat::BOLD . 50 . TextFormat::RESET . TextFormat::RED . " to be able to enchant!";
        $lore[] = "";
        $current = XPUtils::getProgressXP($energy, 0, RPGManager::SATCHEL_MODIFIER);
        $next = XPUtils::levelToXP($level + 1, RPGManager::SATCHEL_MODIFIER) - XPUtils::levelToXP($level, RPGManager::SATCHEL_MODIFIER);
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Energy";
        $progress = (int)XPUtils::getXPProgress($energy, 0, RPGManager::SATCHEL_MODIFIER);
        $times = (int)floor(($progress / 100) * 40);
        $lore[] = TextFormat::RESET . TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . str_repeat("|", $times) . TextFormat::GRAY . str_repeat("|", 40 - $times) . TextFormat::DARK_GRAY . "]";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(" . number_format($current) . "/" . number_format($next) . ")";
        $this->type = $type;
        $this->amount = $amount;
        $this->energy = $energy;
        $this->whitescroll = $whitescrolled;
        $this->enchantments = $enchantments;
        parent::__construct(VanillaItems::NAUTILUS_SHELL(), $customName, $lore, true, true);
    }

    public function getName(): string {
        $name = $this->type->getName();
        switch($this->type->getId()) {
            case ItemIds::EMERALD:
            case ItemIds::EMERALD_ORE:
                $color = TextFormat::GREEN;
                break;
            case ItemIds::DIAMOND:
            case ItemIds::DIAMOND_ORE:
                $color = TextFormat::AQUA;
                break;
            case ItemIds::GOLD_INGOT:
            case ItemIds::GOLD_ORE:
                $color = TextFormat::YELLOW;
                break;
            case ItemIds::REDSTONE:
            case ItemIds::REDSTONE_ORE:
                $color = TextFormat::RED;
                break;
            case ItemIds::DYE:
                $name = "Lapis Lazuli";
                $color = TextFormat::DARK_BLUE;
                break;
            case ItemIds::LAPIS_ORE:
                $color = TextFormat::DARK_BLUE;
                break;
            case ItemIds::IRON_INGOT:
            case ItemIds::IRON_ORE:
                $color = TextFormat::GRAY;
                break;
            case ItemIds::COAL:
            case ItemIds::COAL_ORE:
                $color = TextFormat::DARK_GRAY;
                break;
            default:
                $color = TextFormat::LIGHT_PURPLE;

        }
        $level = XPUtils::xpToLevel($this->energy, RPGManager::SATCHEL_MODIFIER);
        $max = ($level + 1) * 2304;
        $customName = TextFormat::RESET . TextFormat::BOLD . $color . $name . " Satchel " . TextFormat::RESET . TextFormat::GRAY . "(" . TextFormat::GREEN . number_format($this->amount) . TextFormat::GRAY . " / " . TextFormat::WHITE . number_format($max) . TextFormat::GRAY . ")";
        if($level > 0) {
            $customName .= TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . " $level";
        }
        return $customName;
    }

    public function getLore(): array {
        $level = XPUtils::xpToLevel($this->energy, RPGManager::SATCHEL_MODIFIER);
        $max = ($level + 1) * 2304;
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . number_format($this->amount) . TextFormat::GRAY . " / " . TextFormat::WHITE . number_format($max);
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Tool Level " . TextFormat::BOLD . $level;
        $lore[] = TextFormat::RESET . TextFormat::RED . "Needs to be level " . TextFormat::BOLD . 50 . TextFormat::RESET . TextFormat::RED . " to be able to enchant!";
        $lore[] = "";
        $current = XPUtils::getProgressXP($this->energy, 0, RPGManager::SATCHEL_MODIFIER);
        $next = XPUtils::levelToXP($level + 1, RPGManager::SATCHEL_MODIFIER) - XPUtils::levelToXP($level, RPGManager::SATCHEL_MODIFIER);
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Energy";
        $progress = (int)XPUtils::getXPProgress($this->energy, 0, RPGManager::SATCHEL_MODIFIER);
        $times = (int)floor(($progress / 100) * 40);
        $lore[] = TextFormat::RESET . TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . str_repeat("|", $times) . TextFormat::GRAY . str_repeat("|", 40 - $times) . TextFormat::DARK_GRAY . "]";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(" . number_format($current) . "/" . number_format($next) . ")";
        if(count($this->enchantments) > 1) {
            $lore[] = "";
            $lore = array_merge($lore, EnchantmentManager::getLoreByList($this->enchantments));
        }
        if($this->whitescroll === true) {
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . "WHITESCROLLED";
        }
        return $lore;
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::SATCHEL => StringTag::class,
            self::ENERGY => LongTag::class,
            self::AMOUNT => LongTag::class,
            self::ID => IntTag::class,
            self::DAMAGE => IntTag::class,
            self::WHITESCROLLED => ByteTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return Satchel|mixed
     */
    public static function fromItem(Item $item) {
        $tag = self::getCustomTag($item);
        $energy = $tag->getLong(self::ENERGY);
        $amount = $tag->getLong(self::AMOUNT);
        $type = ItemFactory::getInstance()->get($tag->getInt(self::ID), $tag->getInt(self::DAMAGE));
        $whitescrolled = (bool)$tag->getByte(self::WHITESCROLLED);
        $enchantments = $item->getEnchantments();
        return new self($type, $amount, $energy, $whitescrolled, $enchantments);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::SATCHEL, self::SATCHEL);
        $tag->setLong(self::ENERGY, $this->energy);
        $tag->setLong(self::AMOUNT, $this->amount);
        $tag->setInt(self::ID, $this->type->getId());
        $tag->setInt(self::DAMAGE, $this->type->getMeta());
        $tag->setByte(self::WHITESCROLLED, (int)$this->whitescroll);
        return $tag;
    }

    /**
     * @return Item
     */
    public function toItem(): Item {
        $item = parent::toItem();
        foreach($this->enchantments as $enchantment) {
            $item->addEnchantment($enchantment);
        }
        return $item;
    }

    /**
     * @return Item
     */
    public function getType(): Item {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getEnergy(): int {
        return $this->energy;
    }

    /**
     * @param int $energy
     */
    public function setEnergy(int $energy): void {
        $this->energy = $energy;
    }

    /**
     * @param int $energy
     *
     * @return int
     */
    public function addEnergy(int $energy): int {
        $oldLevel = XPUtils::xpToLevel($this->energy, RPGManager::SATCHEL_MODIFIER);
        $this->energy += $energy;
        $newLevel = XPUtils::xpToLevel($this->energy, RPGManager::SATCHEL_MODIFIER);
        return $newLevel - $oldLevel;
    }

    /**
     * @return int
     */
    public function getMaxSubtractableEnergy(): int {
        return XPUtils::getProgressXP($this->energy, 0, RPGManager::SATCHEL_MODIFIER);
    }

    /**
     * @return int
     */
    public function getLevel(): int {
        return XPUtils::xpToLevel($this->energy, RPGManager::SATCHEL_MODIFIER);
    }

    /**
     * @return int
     */
    public function getAmount(): int {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void {
        $level = XPUtils::xpToLevel($this->energy, RPGManager::SATCHEL_MODIFIER);
        $max = ($level + 1) * 2304;
        $this->amount = min($max, $amount);
    }

    /**
     * @return bool
     */
    public function isWhitescroll(): bool {
        return $this->whitescroll;
    }

    /**
     * @param bool $whitescroll
     */
    public function setWhitescroll(bool $whitescroll): void {
        $this->whitescroll = $whitescroll;
    }

    /**
     * @return EnchantmentInstance[]
     */
    public function getEnchantments(): array {
        return $this->enchantments;
    }

    /**
     * @param EnchantmentInstance $enchantment
     */
    public function addEnchantment(EnchantmentInstance $enchantment): void {
        $this->enchantments[EnchantmentIdMap::getInstance()->toId($enchantment->getType())] = $enchantment;
    }

    /**
     * @param Enchantment $enchantment
     *
     * @return int
     */
    public function getEnchantmentLevel(Enchantment $enchantment): int {
        if(isset($this->enchantments[$enchantment->getRuntimeId()])) {
            return $this->enchantments[$enchantment->getRuntimeId()]->getLevel();
        }
        return 0;
    }
}