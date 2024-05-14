<?php

namespace core\game\item\types\vanilla;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\Enchantable;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\zone\ZoneListener;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ToolTier;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class Axe extends \pocketmine\item\Axe implements Enchantable {

    const ORIGINAL_DISPLAY = "OriginalDisplay";
    const ORIGINAL_CUSTOM_NAME = "OriginalCustomName";
    const ORIGINAL_LORE = "OriginalLore";
    const INFO = "Info";
    const WHITESCROLLED = "Whitescrolled";

    /** @var int */
    protected $maxLevel;

    /** @var int */
    protected $tierId;

    /** @var string */
    protected $originalCustomName = "";

    /** @var string[] */
    protected $originalLore = [];

    /** @var bool */
    protected $whitescroll = false;

    /**
     * Axe constructor.
     *
     * @param int $id
     * @param int $maxLevel
     * @param ToolTier $tier
     * @param int $tierId
     * @param string $name
     * @param int $meta
     */
    public function __construct(int $id, int $maxLevel, ToolTier $tier, string $name = "Unknown", int $meta = 0) {
        parent::__construct(new ItemIdentifier($id, $meta), $name, $tier);
        $this->tierId = ZoneListener::ITEM_TO_ARMOR_TIER[$tier->getHarvestLevel()];
        $this->maxLevel = $maxLevel;
    }

    /**
     * @return int
     */
    public function getAttackPoints(): int {
        return ZoneListener::TIER_TO_ATTACK_POINTS[$this->tierId];
    }

    /**
     * @param CompoundTag $tag
     */
    protected function serializeCompoundTag(CompoundTag $tag): void {
        parent::serializeCompoundTag($tag);
        $display = $tag->getCompoundTag(self::ORIGINAL_DISPLAY) ?? new CompoundTag();
        $this->hasOriginalCustomName() ?
            $display->setString(self::ORIGINAL_CUSTOM_NAME, $this->getOriginalCustomName()) :
            $display->removeTag(self::ORIGINAL_CUSTOM_NAME);
        if(count($this->originalLore) > 0) {
            $loreTag = new ListTag();
            foreach($this->originalLore as $line) {
                $loreTag->push(new StringTag($line));
            }
            $display->setTag(self::ORIGINAL_LORE, $loreTag);
        }
        else {
            $display->removeTag(self::ORIGINAL_LORE);
        }
        $display->count() > 0 ?
            $tag->setTag(self::ORIGINAL_DISPLAY, $display) :
            $tag->removeTag(self::ORIGINAL_DISPLAY);
        $info = $tag->getCompoundTag(self::INFO) ?? new CompoundTag();
        $info->setByte(self::WHITESCROLLED, (int)$this->whitescroll);
        $info->count() > 0 ?
            $tag->setTag(self::INFO, $info) :
            $tag->removeTag(self::INFO);
    }

    /**
     * @param CompoundTag $tag
     */
    protected function deserializeCompoundTag(CompoundTag $tag): void {
        parent::deserializeCompoundTag($tag);
        $this->originalCustomName = "";
        $this->originalLore = [];
        $display = $tag->getCompoundTag(self::ORIGINAL_DISPLAY);
        if($display !== null) {
            $this->originalCustomName = $display->getString(self::ORIGINAL_CUSTOM_NAME, $this->originalCustomName);
            $lore = $display->getListTag(self::ORIGINAL_LORE);
            if($lore !== null and $lore->getTagType() === NBT::TAG_String) {
                /** @var StringTag $t */
                foreach($lore as $t) {
                    $this->originalLore[] = $t->getValue();
                }
            }
        }
        $info = $tag->getCompoundTag(self::INFO);
        if($info !== null) {
            $this->whitescroll = (bool)$info->getByte(self::WHITESCROLLED, $this->whitescroll);
        }
    }

    /**
     * @return bool
     */
    public function isWhitescrolled(): bool {
        return $this->whitescroll;
    }

    /**
     * @param bool $value
     *
     * @return Item
     */
    public function setWhitescrolled(bool $value): Item {
        $this->whitescroll = $value;
        return $this;
    }

    /**
     * @param int $amount
     *
     * @return bool
     */
    public function applyDamage(int $amount): bool {
        if($this->isUnbreakable()) {
            return false;
        }
        $amount -= $this->getUnbreakingDamageReduction($amount);
        $this->damage = min($this->damage + $amount, $this->getMaxDurability());
        if($this->isBroken()) {
            $this->onBroken();
        }
        return true;
    }

    /**
     * @return int
     */
    public function getTierId(): int {
        return $this->tier->id();
    }

    /**
     * @return int
     */
    public function getMaxLevel(): int {
        return $this->maxLevel;
    }

    /**
     * @param EnchantmentInstance $enchantment
     *
     * @return $this
     */
    public function addEnchantment(EnchantmentInstance $enchantment): self {
        $type = $enchantment->getType();
        if(EnchantmentManager::canEnchant($this, $type)) {
            if($type instanceof Enchantment and $type->getPremature() !== null) {
                if($this->hasEnchantment(EnchantmentManager::getEnchantment($type->getPremature()))) {
                    parent::removeEnchantment(EnchantmentManager::getEnchantment($type->getPremature()));
                }
            }
        }
        return parent::addEnchantment($enchantment);
    }

    /**
     * @return $this
     */
    public function removeEnchantments(): self {
        return parent::removeEnchantments();
    }

    /**
     * @param \pocketmine\item\enchantment\Enchantment $enchantment
     * @param int $level
     *
     * @return $this
     */
    public function removeEnchantment(\pocketmine\item\enchantment\Enchantment $enchantment, int $level = -1): self {
        return parent::removeEnchantment($enchantment, $level);
    }

    /**
     * @param string $name
     *
     * @return $this|Item
     */
    public function setOriginalCustomName(string $name): Item {
        $this->originalCustomName = $name;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasOriginalCustomName(): bool {
        return $this->originalCustomName !== "";
    }

    /**
     * @return string
     */
    public function getOriginalCustomName(): string {
        return $this->originalCustomName;
    }

    /**
     * @param array $lines
     *
     * @return $this|Item
     */
    public function setOriginalLore(array $lines): Item {
        $this->originalLore = $lines;
        return $this;
    }

    /**
     * @return array
     */
    public function getOriginalLore(): array {
        return $this->originalLore;
    }

    /**
     * @return string[]
     */
    public function getLoreForItem(): array {
        $lore = $this->originalLore;
        $lore[] = "";
        if(count($this->getEnchantments()) > 0) {
            $lore = array_merge($lore, EnchantmentManager::getLoreForItem($this));
            $lore[] = "";
        }
        $lore[] = TextFormat::RESET . TextFormat::RED . "Max level " . TextFormat::WHITE . TextFormat::BOLD . $this->maxLevel;
        $lore[] = "";
        if($this->isWhitescrolled()) {
            $lore[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . "WHITESCROLLED";
            $lore[] = "";
        }
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Required Mining Level " . TextFormat::WHITE . TextFormat::BOLD . ItemManager::getLevelToUseTool($this);
        return $lore;
    }

    /**
     * @return string
     */
    public function getCustomNameForItem(): string {
        $totalLevels = 0;
        foreach($this->getEnchantments() as $ei) {
            $totalLevels += $ei->getLevel();
        }
        $customName = $this->hasOriginalCustomName() ? $this->getOriginalCustomName() : TextFormat::RESET . TextFormat::WHITE . $this->getVanillaName();
        if($totalLevels > 0) {
            $customName .= TextFormat::RESET . " " . TextFormat::BOLD . TextFormat::GREEN . $totalLevels;
        }
        return $customName;
    }

    /**
     * @param int $amount
     *
     * @return int
     */
    protected function getUnbreakingDamageReduction(int $amount): int {
        $unbreakingLevel = 3;
        if($this->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::HARDEN))) {
            $unbreakingLevel += $this->getEnchantmentLevel((EnchantmentManager::getEnchantment(Enchantment::HARDEN)));
        }
        if($this->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::ROCK_HARD))) {
            $unbreakingLevel += 4 + $this->getEnchantmentLevel((EnchantmentManager::getEnchantment(Enchantment::ROCK_HARD)));
        }
        if($this->getTierId() <= ToolTier::IRON()->id()) {
            $unbreakingLevel *= 3;
            if($this->getTierId() === ToolTier::GOLD()->id()) {
                $unbreakingLevel *= 3;
            }
        }
        if($unbreakingLevel > 0) {
            $negated = 0;
            $chance = 1 / ($unbreakingLevel + 1);
            for($i = 0; $i < $amount; ++$i) {
                if(lcg_value() > $chance) {
                    $negated++;
                }
            }
            return $negated;
        }
        return 0;
    }
}