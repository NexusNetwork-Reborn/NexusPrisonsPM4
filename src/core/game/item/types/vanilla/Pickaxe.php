<?php

namespace core\game\item\types\vanilla;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\Rarity;
use core\level\LevelException;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\ToolTier;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class Pickaxe extends \pocketmine\item\Pickaxe {

    const ORIGINAL_DISPLAY = "OriginalDisplay";

    const ORIGINAL_CUSTOM_NAME = "OriginalCustomName";

    const ORIGINAL_LORE = "OriginalLore";

    const INFO = "Info";

    const WHITESCROLLED = "Whitescrolled";

    const ENERGY = "Energy";

    const POINTS = "Points";

    const BLOCKS = "Blocks";

    const CHARGE_SLOTS = "ChargeSlots";

    const CHARGE = "Charge";

    const CHARGES_USED = "ChargesUsed";

    const WM_TIME = "WarpMinerTime";

    const WM_MINED = "WarpMinerMined";

    const SUBTRACTED_FAILURES = "SubtractedFailures";

    const PRESTIGE = "Prestige";

    const ATTRIBUTES = "Attributes";

    const ENERGY_MASTERY = "Energy Mastery";

    const XP_MASTERY = "XP Mastery";

    const HOARDER = "Hoarder";

    const GRINDER = "Grinder";

    const SHARD_MASTERY = "Shard Mastery";

    const METEORITE_MASTERY = "Meteorite Mastery";

    const CLUE_SCROLL_MASTERY = "Clue Scroll Mastery";

    const INQUISITIVE = "Inquisitive";

    const ORE_EXTRACTOR = "Ore Extractor";

    const FORGE_MASTER = "Forge Master";

    /** @var string */
    protected $originalCustomName = "";

    /** @var string[] */
    protected $originalLore = [];

    /** @var bool */
    protected $whitescroll = false;

    /** @var int */
    protected $energy = 0;

    /** @var int */
    protected $points = 0;

    /** @var int */
    protected $blocks = 0;

    /** @var int */
    protected $chargeSlots = 3;

    /** @var int */
    protected $charge = 0;

    /** @var int */
    protected $chargesUsed = 0;

    /** @var int */
    protected $warpMineTime = 0;

    /** @var int */
    protected $warpMined = 0;

    /** @var int */
    protected $prestige = 0;

    /** @var int */
    protected $subtractedFailures = 0;

    /** @var int|float[] */
    protected $attributes = [];

    /**
     * Pickaxe constructor.
     *
     * @param int $id
     * @param int $meta
     * @param string $name
     * @param ToolTier $tier
     */
    public function __construct(int $id, int $meta, string $name, ToolTier $tier) {
        parent::__construct(new ItemIdentifier($id, $meta), $name, $tier);
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
        $info->setLong(self::ENERGY, $this->energy);
        $info->setLong(self::POINTS, $this->points);
        $info->setLong(self::SUBTRACTED_FAILURES, $this->subtractedFailures);
        $info->setLong(self::BLOCKS, $this->blocks);
        $info->setByte(self::CHARGE_SLOTS, $this->chargeSlots);
        $info->setInt(self::CHARGE, $this->charge);
        $info->setByte(self::CHARGES_USED, $this->chargesUsed);
        $info->setLong(self::WM_TIME, $this->warpMineTime);
        $info->setInt(self::WM_MINED, $this->warpMined);
        $info->setByte(self::PRESTIGE, $this->prestige);
        $list = new CompoundTag();
        foreach($this->attributes as $attribute => $i) {
            if(is_int($i)) {
                $list->setInt($attribute, $i);
            }
            if(is_float($i)) {
                if($attribute === self::INQUISITIVE or $attribute === self::SHARD_MASTERY) {
                    $i = round($i, 1);
                }
                if($attribute === self::ORE_EXTRACTOR) {
                    $i = round($i, 3);
                }
                $list->setFloat($attribute, $i);
            }
        }
        $info->setTag(self::ATTRIBUTES, $list);
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
            $this->energy = $info->getLong(self::ENERGY, $this->energy);
            $this->points = $info->getLong(self::POINTS, $this->points);
            $this->subtractedFailures = $info->getLong(self::SUBTRACTED_FAILURES, $this->subtractedFailures);
            $this->blocks = $info->getLong(self::BLOCKS, $this->blocks);
            $this->chargeSlots = $info->getByte(self::CHARGE_SLOTS, $this->chargeSlots);
            $this->charge = $info->getInt(self::CHARGE, $this->charge);
            $this->chargesUsed = $info->getByte(self::CHARGES_USED, $this->chargesUsed);
            $this->warpMineTime = $info->getLong(self::WM_TIME, $this->warpMineTime);
            $this->warpMined = $info->getInt(self::WM_MINED, $this->warpMined);
            $this->prestige = $info->getByte(self::PRESTIGE, $this->prestige);
            $value = $info->getCompoundTag(self::ATTRIBUTES)->getValue();
            foreach($value as $attribute => $tag) {
                $amount = $tag->getValue();
                if($tag instanceof IntTag) {
                    $this->attributes[$attribute] = (int)$amount;
                }
                if($tag instanceof FloatTag) {
                    if($attribute === self::INQUISITIVE or $attribute === self::SHARD_MASTERY) {
                        $amount = round($amount, 1);
                    }
                    if($attribute === self::ORE_EXTRACTOR) {
                        $amount = round($amount, 3);
                    }
                    $this->attributes[$attribute] = (float)$amount;
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isUnbreakable(): bool {
        return true;
    }

    /**
     * @return int
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
     */
    public function addSubtractedFailure(int $amount = 1): void {
        $this->subtractedFailures += $amount;
    }

    /**
     * @param int $amount
     */
    public function setSubtractedFailure(int $amount): void {
        $this->subtractedFailures = $amount;
    }

    /**
     * @return int
     */
    public function getSubtractedFailures(): int {
        return $this->subtractedFailures;
    }

    /**
     * @param int $amount
     *
     * @return Item
     *
     * @throws LevelException
     */
    private function setEnergy(int $amount): Item {
        $amount = max(0, $amount);
        $this->energy = $amount;
        return $this;
    }

    /**
     * @param int $amount
     *
     * @return Item
     */
    public function subtractEnergy(int $amount): Item {
        return $this->setEnergy($this->getEnergy() - $amount);
    }

    /**
     * @return int
     */
    public function getMaxSubtractableEnergy(): int {
        return XPUtils::getProgressXP($this->getEnergy(), 0, RPGManager::ENERGY_MODIFIER);
    }

    /**
     * @param int $amount
     * @param NexusPlayer|null $player
     *
     * @return Item
     * @throws LevelException
     */
    public function addEnergy(int $amount, ?NexusPlayer $player = null): Item {
        if($player !== null) {
            $amount = (int)($amount * $player->getDataSession()->getEnergyModifier() * (1 + ($this->getCharge() / 100)));
        }
        $oldLevel = XPUtils::xpToLevel($this->getEnergy(), RPGManager::ENERGY_MODIFIER);
        $newLevel = XPUtils::xpToLevel($this->getEnergy() + $amount, RPGManager::ENERGY_MODIFIER);
        if($newLevel > $oldLevel) {
            $points = $this->getPoints() + ($newLevel - $oldLevel);
            if($player !== null) {
                $player->playOrbSound();
                $player->sendTitle(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Pickaxe", TextFormat::GRAY . "$points available points");
            }
            $this->setEnergy($amount + $this->getEnergy());
            return $this->setPoints($points);
        }
        return $this->setEnergy($amount + $this->getEnergy());
    }

    /**
     * @return int
     */
    public function getEnergy(): int {
        return $this->energy;
    }

    /**
     * @param int $amount
     *
     * @return Item
     *
     * @throws LevelException
     */
    private function setChargeSlot(int $amount): Item {
        $amount = max(0, $amount);
        $this->chargeSlots = $amount;
        return $this;
    }

    /**
     * @return Item
     * @throws LevelException
     */
    public function addChargeSlot(): Item {
        $slots = $this->getChargeSlots() + 1;
        return $this->setChargeSlot(min(10 + $this->getAttribute(self::ENERGY_MASTERY), $slots));
    }

    /**
     * @return int
     */
    public function getChargeSlots(): int {
        return $this->chargeSlots;
    }

    /**
     * @param int $amount
     *
     * @return Item
     *
     * @throws LevelException
     */
    private function setCharge(int $amount): Item {
        $amount = max(0, $amount);
        $this->charge = $amount;
        return $this;
    }

    /**
     * @param int $charge
     *
     * @return Item
     * @throws LevelException
     */
    public function addCharge(int $charge): Item {
        if($this->getChargesUsed() >= $this->getChargeSlots()) {
            return $this;
        }
        $this->addChargesUsed();
        return $this->setCharge($this->getCharge() + $charge);
    }

    /**
     * @return int
     */
    public function getCharge(): int {
        return $this->charge;
    }

    /**
     * @param int $amount
     *
     * @return Item
     *
     * @throws LevelException
     */
    private function setChargesUsed(int $amount): Item {
        $amount = max(0, $amount);
        $this->chargesUsed = $amount;
        return $this;
    }

    /**
     * @return Item
     *
     * @throws LevelException
     */
    public function addChargesUsed(): Item {
        return $this->setChargesUsed(min(10 + $this->getAttribute(self::ENERGY_MASTERY), $this->getChargesUsed() + 1));
    }

    /**
     * @return int
     */
    public function getChargesUsed(): int {
        return $this->chargesUsed;
    }

    /**
     * @param int $amount
     *
     * @return Item
     *
     * @throws LevelException
     */
    private function setPoints(int $amount): Item {
        $amount = max(0, $amount);
        $this->points = $amount;
        return $this;
    }

    /**
     * @return int
     */
    public function getPoints(): int {
        return $this->points;
    }

    /**
     * @param int $amount
     *
     * @return Item
     */
    public function subtractPoints(int $amount): Item {
        return $this->setPoints($this->getPoints() - $amount);
    }

    /**
     * @param int $amount
     *
     * @return Item
     */
    private function setPrestige(int $amount): Item {
        $amount = max(0, $amount);
        $this->prestige = $amount;
        return $this;
    }

    /**
     * @return Item
     */
    public function addPrestige(): Item {
        $slots = $this->getPrestige() + 1;
        $this->setEnergy(0);
        $this->removeEnchantments();
        $this->setPoints(0);
        $this->setSubtractedFailure(0);
        return $this->setPrestige(min(10, $slots));
    }

    /**
     * @return int
     */
    public function getPrestige(): int {
        return $this->prestige;
    }

    /**
     * @param string $attribute
     * @param int|float $amount
     *
     * @return Item
     */
    public function setAttribute(string $attribute, $amount): Item {
        $amount = max(0, $amount);
        $this->attributes[$attribute] = $amount;
        return $this;
    }

    /**
     * @param string $attribute
     *
     * @return int|float
     */
    public function getAttribute(string $attribute) {
        return $this->attributes[$attribute] ?? 0;
    }

    /**
     * @return int
     */
    public function getBlocks(): int {
        return $this->blocks;
    }

    /**
     * @return Item
     *
     */
    public function addBlock(): Item {
        ++$this->blocks;
        return $this;
    }

    /**
     * @param int $blocks
     *
     * @return Item
     */
    public function setBlocks(int $blocks): Item {
        $this->blocks = $blocks;
        return $this;
    }

    /**
     * @return int
     */
    public function getWarpMinerTime(): int {
        if($this->warpMineTime <= 0) {
            $this->warpMineTime = time();
        }
        return $this->warpMineTime;
    }

    /**
     * @return int
     */
    public function getWarpMinerMined(): int {
        return $this->warpMined;
    }

    /**
     * @param NexusPlayer $player
     * @return Item
     */
    public function addWarpMinerMined(NexusPlayer $player): Item {
        $mined = $this->getWarpMinerMined();
        if((time() - $this->getWarpMinerTime()) >= 86400) {
            $this->warpMineTime = time();
            $mined = 0;
        }
        $max = 0;
        if($this->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::WARP_MINER))) {
            $max = $this->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::TIME_WARP)) * 2000;
            if(SetUtils::isWearingFullSet($player, "koth")) $max *= 2;
            if(SetUtils::isWearingFullSet($player, "koth") && $this->getNamedTag()->getString(SetManager::SET, "") === "koth") $max *= 1.1;
            if($mined >= $max) {
                return $this;
            }
        }
        if($this->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::TIME_WARP))) {
            $max = $this->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::TIME_WARP)) * 4000;
            if(SetUtils::isWearingFullSet($player, "koth") && $this->getNamedTag()->getString(SetManager::SET, "") === "koth") $max *= 1.1;
            if($mined >= $max) {
                return $this;
            }
        }
        ++$this->warpMined;
        return $this;
    }

    /**
     * @param bool $isCorrectTool
     *
     * @return float
     */
    public function getMiningEfficiency(bool $isCorrectTool): float {
        $efficiency = 1;
        if($isCorrectTool) {
            $efficiency = $this->getBaseMiningEfficiency();
            if(($enchantmentLevel = $this->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::EFFICIENCY2))) > 0){
                $efficiency += ($enchantmentLevel ** 2 - 1);
            }
            elseif(($enchantmentLevel = $this->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::METICULOUS_EFFICIENCY))) > 0){
                $efficiency += (($enchantmentLevel + 2) ** 2 + 2);
            }
            if($this->getNamedTag()->getString(SetManager::SET, "") === "demolition") $efficiency *= 1.5;
        }
        return $efficiency;
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
        $level = XPUtils::xpToLevel($this->getEnergy(), RPGManager::ENERGY_MODIFIER);
        $lore = $this->getOriginalLore();
        if($this->hasItemSkin()) {
            $lore[] = TextFormat::RESET . TextFormat::BLUE . "Nature";
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::BLUE . "+" . ($this->getAttackPoints() - 1) . " Attack Damage";
        }
        $lore[] = "";
        $itemManager = Nexus::getInstance()->getGameManager()->getItemManager();
        if(!empty($this->attributes)) {
            foreach($this->attributes as $id => $value) {
                $prestige = $itemManager->getPickaxePrestige($id);
                $lore[] = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . $prestige->getName() . TextFormat::RESET . TextFormat::GRAY . " - " . $prestige->getLore($this);
            }
            $lore[] = "";
        }
        if(count($this->getEnchantments()) > 0) {
            $lore = array_merge($lore, EnchantmentManager::getLoreForItem($this));
            $lore[] = "";
        }
        if($this->isWhitescrolled()) {
            $lore[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . "WHITESCROLLED";
            $lore[] = "";
        }
        $levels = 0;
        foreach($this->getEnchantments() as $enchantment) {
            $levels += $enchantment->getLevel();
        }
        $lore[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . $levels . TextFormat::GREEN . " Enchants";
        $failures = max(0, ($level - $this->getPoints() - $levels) - $this->subtractedFailures);
        $lore[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . $failures . TextFormat::RED . " Failures";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Blocks " . TextFormat::BOLD . number_format($this->getBlocks());
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Points " . TextFormat::BOLD . number_format($this->getPoints());
        $lore[] = "";
        $current = XPUtils::getProgressXP($this->getEnergy(), 0, RPGManager::ENERGY_MODIFIER);
        $next = XPUtils::levelToXP($level + 1, RPGManager::ENERGY_MODIFIER) - XPUtils::levelToXP($level, RPGManager::ENERGY_MODIFIER);
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Energy";
        $progress = (int)XPUtils::getXPProgress($this->getEnergy(), 0, RPGManager::ENERGY_MODIFIER);
        $times = (int)floor(($progress / 100) * 40);
        $lore[] = TextFormat::RESET . TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . str_repeat("|", $times) . TextFormat::GRAY . str_repeat("|", 40 - $times) . TextFormat::DARK_GRAY . "]";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(" . number_format($current) . "/" . number_format($next) . ")";
        $lore[] = "";
        if($this->getChargesUsed() > 0) {
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . $this->getCharge() . "% " . TextFormat::AQUA . "Energy Gain" . TextFormat::RESET . TextFormat::GRAY . " from " . TextFormat::WHITE . TextFormat::BOLD . $this->getChargesUsed() . TextFormat::RESET . TextFormat::AQUA . " Charge Orbs";
        }
        $maxSlots = 10 + $this->getAttribute(self::ENERGY_MASTERY);
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . $this->getChargeSlots() . TextFormat::RESET . TextFormat::GRAY . "/" . TextFormat::WHITE . $maxSlots . TextFormat::AQUA . " Charge Orb " . TextFormat::GRAY . "slots unlocked";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Required Mining Level " . TextFormat::WHITE . TextFormat::BOLD . ItemManager::getLevelToUseTool($this);
        return $lore;
    }

    /**
     * @return string
     */
    public function getCustomNameForItem(): string {
        $level = XPUtils::xpToLevel($this->getEnergy(), RPGManager::ENERGY_MODIFIER);
        $prestige = $this->getPrestige() > 0 ?  " " . EnchantmentManager::getRomanNumber($this->getPrestige()) : "";
        $customName = $this->hasOriginalCustomName() ? $this->getOriginalCustomName() : TextFormat::RESET . TextFormat::WHITE . $this->getVanillaName();
        if($level > 0) {
            $customName .= TextFormat::RESET . " " . TextFormat::BOLD . TextFormat::GREEN . $level;
        }
        if($this->prestige > 0) {
            $customName .= TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . $prestige;
        }
        if ($this->hasItemSkin()) {
            $rarity = ItemManager::getSkinScroll(ItemManager::getIdentifier($this->getId()))->getRarity();
            return "(" . $this->nameFromTier($this->getTier()) . ") " . Rarity::RARITY_TO_COLOR_MAP[$rarity] . $customName;
        }
        return $customName;
    }

    private const PICKAXE_IDS = [ItemIds::WOODEN_PICKAXE, ItemIds::STONE_PICKAXE, ItemIds::IRON_PICKAXE, ItemIds::DIAMOND_PICKAXE, ItemIds::GOLDEN_PICKAXE];

    public function hasItemSkin(): bool {
        return !in_array($this->getId(), self::PICKAXE_IDS);
    }

    public function nameFromTier(ToolTier $tier) : string {
        switch($tier) {
            case ToolTier::WOOD():
                return "Wooden";
            case ToolTier::STONE():
                return "Stone";
            case ToolTier::IRON():
                return "Iron";
            case ToolTier::GOLD():
                return "Golden";
            case ToolTier::DIAMOND():
                return "Diamond";
        }
        return "";
    }

    public function copyInto(Item $item): Item {
        foreach(get_object_vars($this) as $key => $value) {
            $item->{$key} = $value;
        }
        $item->setCount($this->getCount());
        if($item instanceof Durable) {
            $item->setDamage($this->getDamage());
        }
        $item->setCustomName($this->getCustomNameForItem());
        $item->setLore($this->getLoreForItem());
        $item->setNamedTag($this->getNamedTag());
        if($item instanceof Pickaxe && !$item->hasItemSkin()) {
            $item->setLore($item->getLoreForItem());
        }
        return $item;
    }
}