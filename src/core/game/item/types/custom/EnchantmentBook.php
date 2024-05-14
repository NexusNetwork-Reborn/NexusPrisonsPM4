<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\event\FailEnchantmentEvent;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Sword;
use core\player\NexusPlayer;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\AnvilBreakSound;
use pocketmine\world\sound\AnvilUseSound;

class EnchantmentBook extends Interactive {

    const ENCHANTMENT = "EnchantmentBook";

    const LEVEL = "Level";

    const SUCCESS = "Success";

    const DESTROY = "Destroy";

    const ENERGY = "Energy";

    /** @var EnchantmentInstance */
    private $enchantment;

    /** @var int */
    private $success;

    /** @var int */
    private $destroy;

    /** @var int */
    private $energy;

    /**
     * EnchantmentBook constructor.
     *
     * @param EnchantmentInstance $enchantment
     * @param int $success
     * @param int $destroy
     * @param int $energy
     * @param string|null $uuid]
     */
    public function __construct(EnchantmentInstance $enchantment, int $success, int $destroy, int $energy = 0, ?string $uuid = null) {
        /** @var Enchantment $type */
        $type = $enchantment->getType();
        $maxLevel = "";
        if($enchantment->getLevel() === $type->getMaxLevel()) {
            $maxLevel = TextFormat::BOLD;
        }
        $customName = TextFormat::RESET . Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$type->getRarity()]] . $maxLevel . $type->getName() . TextFormat::AQUA . " " . EnchantmentManager::getRomanNumber($enchantment->getLevel()) . TextFormat::RESET . TextFormat::GRAY . " (" . TextFormat::WHITE . "$success%" . TextFormat::GRAY .")";
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::ITALIC . TextFormat::GRAY . wordwrap($type->getDescription(), 25, "\n");
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "$success% success rate";
        $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "$destroy% destroy rate";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Energy";
        $times = (int)round(($energy / EnchantmentManager::getNeededEnergy($enchantment)) * 40);
        $lore[] = TextFormat::RESET . TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . str_repeat("|", $times) . TextFormat::GRAY . str_repeat("|", 40 - $times) . TextFormat::DARK_GRAY . "]";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(" . number_format($energy) . "/" . number_format(EnchantmentManager::getNeededEnergy($enchantment)) . ")";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Maximum Level: " . TextFormat::WHITE . EnchantmentManager::getRomanNumber($type->getMaxLevel());
        $flags = EnchantmentManager::flagToString($type->getPrimaryItemFlags());
        if(EnchantmentManager::flagToString($type->getSecondaryItemFlags()) !== "None") {
            $flags .= ", " . EnchantmentManager::flagToString($type->getSecondaryItemFlags());
        }
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Applicable to: " . $flags;
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Drag this onto the";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "item you want to enchant";
        // TODO: Impossible level fancy
        $this->enchantment = $enchantment;
        $this->success = $success;
        $this->destroy = $destroy;
        $this->energy = $energy;
        parent::__construct(ItemFactory::getInstance()->get(ItemIds::ENCHANTED_BOOK), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return string
     */
    public function getName(): string {
        /** @var Enchantment $type */
        $type = $this->enchantment->getType();
        $maxLevel = "";
        if($this->enchantment->getLevel() === $type->getMaxLevel()) {
            $maxLevel = TextFormat::BOLD;
        }
        return TextFormat::RESET . Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$type->getRarity()]] . $maxLevel . $type->getName() . TextFormat::AQUA . " " . EnchantmentManager::getRomanNumber($this->enchantment->getLevel()) . TextFormat::RESET . TextFormat::GRAY . " (" . TextFormat::WHITE . "$this->success%" . TextFormat::GRAY .")";
    }

    /**
     * @return string[]
     */
    public function getLore(): array {
        /** @var Enchantment $type */
        $type = $this->enchantment->getType();
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::ITALIC . TextFormat::GRAY . wordwrap($type->getDescription(), 25, "\n");
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "$this->success% success rate";
        $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "$this->destroy% destroy rate";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Energy";
        $times = (int)round(($this->energy / EnchantmentManager::getNeededEnergy($this->enchantment)) * 40);
        $lore[] = TextFormat::RESET . TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . str_repeat("|", $times) . TextFormat::GRAY . str_repeat("|", 40 - $times) . TextFormat::DARK_GRAY . "]";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(" . number_format($this->energy) . "/" . number_format(EnchantmentManager::getNeededEnergy($this->enchantment)) . ")";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Maximum Level: " . TextFormat::WHITE . EnchantmentManager::getRomanNumber($type->getMaxLevel());
        $flags = EnchantmentManager::flagToString($type->getPrimaryItemFlags());
        if(EnchantmentManager::flagToString($type->getSecondaryItemFlags()) !== "None") {
            $flags .= ", " . EnchantmentManager::flagToString($type->getSecondaryItemFlags());
        }
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Applicable to: " . $flags;
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Drag this onto the";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "item you want to enchant";
        return $lore;
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $enchantment = EnchantmentManager::getEnchantment($tag->getInt(self::ENCHANTMENT));
        $level = $tag->getInt(self::LEVEL);
        $success = $tag->getInt(self::SUCCESS);
        $destroy = $tag->getInt(self::DESTROY);
        $energy = $tag->getLong(self::ENERGY);
        $uuid = $tag->getString(self::UUID);
        return new self(new EnchantmentInstance($enchantment, $level), $success, $destroy, $energy, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setInt(self::ENCHANTMENT, EnchantmentIdMap::getInstance()->toId($this->enchantment->getType()));
        $tag->setInt(self::LEVEL, $this->enchantment->getLevel());
        $tag->setInt(self::SUCCESS, $this->success);
        $tag->setInt(self::DESTROY, $this->destroy);
        $tag->setLong(self::ENERGY, $this->energy);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::ENCHANTMENT => IntTag::class,
            self::LEVEL => IntTag::class,
            self::SUCCESS => IntTag::class,
            self::DESTROY => IntTag::class,
            self::ENERGY => LongTag::class
        ];
    }

    /**
     * @return EnchantmentInstance
     */
    public function getEnchantment(): EnchantmentInstance {
        return $this->enchantment;
    }

    /**
     * @return int
     */
    public function getSuccess(): int {
        return $this->success;
    }

    /**
     * @param int $success
     */
    public function setSuccess(int $success): void {
        $this->success = $success;
    }

    /**
     * @return int
     */
    public function getDestroy(): int {
        return $this->destroy;
    }

    /**
     * @param int $destroy
     */
    public function setDestroy(int $destroy): void {
        $this->destroy = $destroy;
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
        $this->energy = min($energy, EnchantmentManager::getNeededEnergy($this->enchantment));
    }

    /**
     * @param NexusPlayer $player
     * @param Item $itemClickedWith
     * @param Item $itemClicked
     * @param SlotChangeAction $itemClickedWithAction
     * @param SlotChangeAction $itemClickedAction
     *
     * @return bool
     */
    public function onCombine(NexusPlayer $player, Item $itemClickedWith, Item $itemClicked, SlotChangeAction $itemClickedWithAction, SlotChangeAction $itemClickedAction): bool {
        $enchantment = $this->getEnchantment()->getType();
        if($itemClicked instanceof Armor or $itemClicked instanceof Axe or $itemClicked instanceof Sword or $itemClicked instanceof Bow) {
            $totalLevels = 0;
            $level = $this->getEnchantment()->getLevel();
            foreach($itemClicked->getEnchantments() as $ei) {
                if(!$ei->getType() instanceof Enchantment) continue;
                if($enchantment->getPremature() === $ei->getType()->getRuntimeId()) {
                    continue;
                }
                $totalLevels += $ei->getLevel();
            }
            if($totalLevels + $level > $itemClicked->getMaxLevel()) {
                $player->playErrorSound();
                return true;
            }
            if(EnchantmentManager::canEnchant($itemClicked, $enchantment)) {
                if($level <= $itemClicked->getEnchantmentLevel($enchantment)) {
                    $player->playErrorSound();
                    return true;
                }
                $energy = $this->getEnergy();
                $enchantmentInstance = new EnchantmentInstance($enchantment, $level);
                if($energy < EnchantmentManager::getNeededEnergy($enchantmentInstance)) {
                    $player->playErrorSound();
                    return true;
                }
                $success = $this->getSuccess();
                $destroy = $this->getDestroy();
                if($success >= mt_rand(1, 100)) {
                    $itemClickedAction->getInventory()->removeItem($itemClicked);
                    $itemClicked->addEnchantment($enchantmentInstance);
                    $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound());
                    $itemClickedAction->getInventory()->addItem($itemClicked);
                }
                else {
                    if($destroy >= mt_rand(1, 100)) {
                        if($itemClicked->isWhitescrolled()) {
                            $itemClicked->setWhitescrolled(false);
                            $player->sendTranslatedMessage("savedByWhitescroll");
                        }
                        else {
                            $itemClickedAction->getInventory()->removeItem($itemClicked);
                        }
                        $ev = new FailEnchantmentEvent($player);
                        $ev->call();
                        $player->getWorld()->addSound($player->getPosition(), new AnvilBreakSound());
                    }
                }
                $this->setUsed();
                $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
                return false;
            }
            $player->playErrorSound();
        }
        return true;
    }
}