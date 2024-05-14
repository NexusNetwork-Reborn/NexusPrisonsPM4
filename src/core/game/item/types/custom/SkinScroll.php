<?php

namespace core\game\item\types\custom;

use core\command\forms\ItemSkinInfoForm;
use core\command\inventory\ItemSkinInfoInventory;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\player\NexusPlayer;
use customiesdevs\customies\item\CustomiesItemFactory;
use pocketmine\block\Block;
use pocketmine\block\BlockToolType;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ToolTier;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\AnvilUseSound;

class SkinScroll extends Interactive {

    public const SKIN_IDENTIFIER = "SkinIdentifier";
    public const TOOL_TYPE = "ToolType";
    public const SKIN_NAME = "SkinName";
    public const RARITY = "Rarity";

    private $skinId;

    private $toolType;

    private $skinName;

    private $rarity;

    public function __construct(string $skinId, int $toolType, string $skinName, string $rarity)
    {
        $customName = TextFormat::RESET . TextFormat::BLUE . TextFormat::BOLD . "Item Skin Scroll " . TextFormat::RESET . TextFormat::WHITE . "(" . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "$skinName" . TextFormat::WHITE .")";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Drag this on top of a " . TextFormat::BOLD . $this->toolTypeToName($toolType);
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "to apply the item skin.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Right-click to see more info.";
        $this->skinId = $skinId;
        $this->toolType = $toolType;
        $this->skinName = $skinName;
        $this->rarity = $rarity;
        parent::__construct(CustomiesItemFactory::getInstance()->get("nexus:skin_scroll"), $customName, $lore, true);
    }

    public function getToolType(): int
    {
        return $this->toolType;
    }

    public function getSkinName(): string
    {
        return $this->skinName;
    }

    public function getSkinId(): string
    {
        return $this->skinId;
    }

    public function getName(): string
    {
        return TextFormat::RESET . TextFormat::BLUE . TextFormat::BOLD . "Item Skin Scroll " . TextFormat::RESET . TextFormat::GRAY . "(" . Rarity::RARITY_TO_COLOR_MAP[$this->rarity] . "$this->skinName" . TextFormat::GRAY .")";
    }

    public function getFancyName() {
        return Rarity::RARITY_TO_COLOR_MAP[$this->rarity] . $this->skinName;
    }

    public function getRarity(): string
    {
        return $this->rarity;
    }

    public function getLore(): array
    {
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Can apply to your " . TextFormat::BOLD . self::toolTypeToName($this->toolType) . ".";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Use /is to set your skin.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::AQUA . "RIGHT-CLICK" . TextFormat::GRAY . " to see more info.";
        $lore[] = TextFormat::RESET . TextFormat::AQUA . "RIGHT-CLICK + SNEAK" . TextFormat::GRAY . " to unlock the skin.";
        return $lore;
    }

    public static function getRequiredTags(): array
    {
        return [
            self::SKIN_IDENTIFIER => StringTag::class,
            self::TOOL_TYPE => IntTag::class,
            self::SKIN_NAME => StringTag::class,
            self::RARITY => StringTag::class
        ];
    }

    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $skinId = $tag->getString(self::SKIN_IDENTIFIER);
        $toolType = $tag->getInt(self::TOOL_TYPE);
        $skinName = $tag->getString(self::SKIN_NAME);
        $rarity = $tag->getString(self::RARITY);
        return new self($skinId, $toolType, $skinName, $rarity);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::SKIN_IDENTIFIER, $this->skinId);
        $tag->setInt(self::TOOL_TYPE, $this->toolType);
        $tag->setString(self::SKIN_NAME, $this->skinId);
        $tag->setString(self::RARITY, $this->rarity);
        return $tag;
    }

    public function toolTypeToName(int $type) : string {
        return match ($type) {
            BlockToolType::PICKAXE => "pickaxe",
            BlockToolType::SWORD => "sword",
            BlockToolType::AXE => "axe"
        };
    }

    public function onInteract(NexusPlayer $player, Inventory $inventory, Item $item, int $face, Block $block): void
    {
        $skinID = $this->getSkinId();
        $name = $this->getSkinName();
        $tool = $this->getToolType();
        $rarity = $this->getRarity();
        $copy = new SkinScroll($skinID, $tool, $name, $rarity);
        $ds = $player->getDataSession();
        if($player->isSneaking()){
            if($ds->hasItemSkin($skinID)) {
                $player->sendMessage(TextFormat::RED . "You have already unlocked this item skin!");
            } else {
                $inventory->setItemInHand($item->setCount($item->getCount() - 1));
                $ds->addItemSkin($skinID);
                $fancyName = str_replace("_", " ", $name);
                $fancyName = TextFormat::GOLD . ucwords($fancyName) . TextFormat::GREEN;
                $player->sendMessage(TextFormat::GREEN . "You have unlocked the " . $fancyName . " item skin!");
                $player->sendMessage(TextFormat::AQUA . "Use /is to equip it on your " . self::toolTypeToName($tool) . ".");
            }
        } else {
            (new ItemSkinInfoInventory($copy))->send($player);
        }
    }

//    public function onCombine(NexusPlayer $player, Item $itemClickedWith, Item $itemClicked, SlotChangeAction $itemClickedWithAction, SlotChangeAction $itemClickedAction): bool
//    {
//        if($itemClicked->getBlockToolType() === $this->toolType) {
//            $new = $this->makeNewItem($itemClicked->setCount(1));
//            if($new !== null) {
//                if($itemClicked instanceof Sword && $itemClicked->hasItemSkin()) {
//                    $player->sendMessage(TextFormat::RED . "Your weapon already has an item skin, use /removeitemskin first!");
//                    return true;
//                } elseif($itemClicked instanceof Pickaxe && $itemClicked->hasItemSkin()) {
//                    $player->sendMessage(TextFormat::RED . "Your pickaxe already has an item skin, use /removeitemskin first!");
//                    return true;
//                }
//                $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound());
//                $itemClickedAction->getInventory()->removeItem($itemClicked->setCount(1));
//                $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
//
//                $itemClickedAction->getInventory()->addItem($new);
//            }
//            return false;
//        }
//        return true;
//    }

    public function makeNewItem(Item $original) : ?Item {
        $skin = $this->skinId;
        switch($this->toolType) {
            case BlockToolType::PICKAXE:
                if($original instanceof Pickaxe) {
                    return $original->copyInto(CustomiesItemFactory::getInstance()->get("nexus:$skin" . "_" . $this->tierToName($original->getTier())));
                }
                break;
            case BlockToolType::SWORD:
                if($original instanceof Sword) {
                    return $original->copyInto(CustomiesItemFactory::getInstance()->get("nexus:$skin" . "_" . $this->tierToName($original->getTier())));
                }
        }
        return null;
    }

    private function tierToName(ToolTier $tier) : string {
        switch ($tier) {
            case ToolTier::WOOD():
                return "wood";
            case ToolTier::STONE():
                return "stone";
            case ToolTier::IRON():
                return "iron";
            case ToolTier::DIAMOND():
                return "diamond";
            case ToolTier::GOLD():
                return "gold";
        }
        return "";
    }

}