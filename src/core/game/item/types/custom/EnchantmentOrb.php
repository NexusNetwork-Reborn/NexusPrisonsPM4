<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\AnvilBreakSound;
use pocketmine\world\sound\AnvilUseSound;

class EnchantmentOrb extends Interactive {

    const ENCHANTMENT = "EnchantmentOrb";

    const LEVEL = "Level";

    const SUCCESS = "Success";

    const RARITY_TO_DAMAGE_MAP = [
        Enchantment::SIMPLE => 8,
        Enchantment::UNCOMMON => 10,
        Enchantment::ELITE => 6,
        Enchantment::ULTIMATE => 11,
        Enchantment::LEGENDARY => 14,
        Enchantment::GODLY => 1,
        Enchantment::EXECUTIVE => 13
    ];

    /** @var EnchantmentInstance */
    private $enchantment;

    /** @var int */
    private $success;

    /**
     * EnchantmentOrb constructor.
     *
     * @param EnchantmentInstance $enchantment
     * @param int $success
     * @param string|null $uuid
     */
    public function __construct(EnchantmentInstance $enchantment, int $success, ?string $uuid = null) {
        /** @var Enchantment $type */
        $type = $enchantment->getType();
        $maxLevel = "";
        if($enchantment->getLevel() === $type->getMaxLevel()) {
            $maxLevel = TextFormat::BOLD;
        }
        $customName = TextFormat::RESET . Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$type->getRarity()]] . $maxLevel . $type->getName() . TextFormat::AQUA . " " . EnchantmentManager::getRomanNumber($enchantment->getLevel()) . TextFormat::RESET . TextFormat::GRAY . " (" . TextFormat::WHITE . "$success%" . TextFormat::GRAY .")";
        $lore = [];
        $flags = EnchantmentManager::flagToString($type->getPrimaryItemFlags());
        if(EnchantmentManager::flagToString($type->getSecondaryItemFlags()) !== "None") {
            $flags .= ", " . EnchantmentManager::flagToString($type->getSecondaryItemFlags());
        }
        $lore[] = TextFormat::RESET . TextFormat::GRAY . $flags . " Enchant";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "$success% success rate";
        $fail = 100 - $success;
        $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "$fail% failure rate";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . wordwrap($type->getDescription(), 25, "\n");
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Applicable to: " . TextFormat::WHITE . $flags;
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Pickaxe: Drop in wormhole";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Satchel: Drag 'n Drop";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::RED . "The item must have the";
        $lore[] = TextFormat::RESET . TextFormat::RED . "previous level.";
        $this->enchantment = $enchantment;
        $this->success = $success;
        parent::__construct(ItemFactory::getInstance()->get(ItemIds::DYE, self::RARITY_TO_DAMAGE_MAP[$type->getRarity()]), $customName, $lore, true, true, $uuid);
    }

    public function getName(): string {
        $type = $this->enchantment->getType();
        $maxLevel = "";
        if($this->enchantment->getLevel() === $type->getMaxLevel()) {
            $maxLevel = TextFormat::BOLD;
        }
        return TextFormat::RESET . Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$type->getRarity()]] . $maxLevel . $type->getName() . TextFormat::AQUA . " " . EnchantmentManager::getRomanNumber($this->enchantment->getLevel()) . TextFormat::RESET . TextFormat::GRAY . " (" . TextFormat::WHITE . "$this->success%" . TextFormat::GRAY .")";
    }

    public function getLore(): array {
        $lore = [];
        $type = $this->enchantment->getType();
        $flags = EnchantmentManager::flagToString($type->getPrimaryItemFlags());
        if(EnchantmentManager::flagToString($type->getSecondaryItemFlags()) !== "None") {
            $flags .= ", " . EnchantmentManager::flagToString($type->getSecondaryItemFlags());
        }
        $lore[] = TextFormat::RESET . TextFormat::GRAY . $flags . " Enchant";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "$this->success% success rate";
        $fail = 100 - $this->success;
        $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "$fail% failure rate";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . wordwrap($type->getDescription(), 25, "\n");
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Applicable to: " . TextFormat::WHITE . $flags;
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Pickaxe: Drop in wormhole";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Satchel: Drag 'n Drop";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::RED . "The item must have the";
        $lore[] = TextFormat::RESET . TextFormat::RED . "previous level.";
        return $lore;
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::ENCHANTMENT => IntTag::class,
            self::LEVEL => IntTag::class,
            self::SUCCESS => IntTag::class
        ];
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
        $uuid = $tag->getString(self::UUID);
        return new self(new EnchantmentInstance($enchantment, $level), $success, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setInt(self::ENCHANTMENT, EnchantmentIdMap::getInstance()->toId($this->enchantment->getType()));
        $tag->setInt(self::LEVEL, $this->enchantment->getLevel());
        $tag->setInt(self::SUCCESS, $this->success);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
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
     * @param NexusPlayer $player
     * @param Item $itemClickedWith
     * @param Item $itemClicked
     * @param SlotChangeAction $itemClickedWithAction
     * @param SlotChangeAction $itemClickedAction
     *
     * @return bool
     */
//    public function onCombine(NexusPlayer $player, Item $itemClickedWith, Item $itemClicked, SlotChangeAction $itemClickedWithAction, SlotChangeAction $itemClickedAction): bool {
//        $enchantment = $this->getEnchantment()->getType();
//        if(Satchel::isInstanceOf($itemClicked)) {
//            $satchel = Satchel::fromItem($itemClicked);
//            if($enchantment->getRuntimeId() === Enchantment::LUCKY or $enchantment->getRuntimeId() === Enchantment::ETERNAL_LUCK) {
//                return true;
//            }
//            if(EnchantmentManager::canEnchant($itemClicked, $enchantment)) {
//                $level = $this->getEnchantment()->getLevel();
//                if($level - $itemClicked->getEnchantmentLevel($enchantment) > 1) {
//                    $player->playErrorSound();
//                    return true;
//                }
//                $energy = $satchel->getEnergy();
//                $energyLevel = XPUtils::xpToLevel($energy, RPGManager::SATCHEL_MODIFIER);
//                if($energyLevel < 50) {
//                    $player->playErrorSound();
//                    return true;
//                }
//                $enchantmentInstance = new EnchantmentInstance($enchantment, $level);
//                $success = $this->getSuccess();
//                if($success >= mt_rand(1, 100)) {
//                    $itemClicked->addEnchantment($enchantmentInstance);
//                    $itemClickedAction->getInventory()->removeItem($itemClicked);
//                    $itemClickedAction->getInventory()->addItem(Satchel::fromItem($itemClicked)->toItem());
//                    $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound());
//                }
//                else {
//                    $player->getWorld()->addSound($player->getPosition(), new AnvilBreakSound());
//                }
//                $this->setUsed();
//                $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
//                return false;
//            }
//        }
//        return true;
//    }
}