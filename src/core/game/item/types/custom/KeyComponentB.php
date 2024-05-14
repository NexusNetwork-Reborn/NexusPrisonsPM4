<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class KeyComponentB extends Interactive {

    const KEY_COMPONENT_B = "KeyComponentB";

    /**
     * Absorber constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::BLUE . TextFormat::BOLD . "Key Component B";
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "A broken key fragment, discovered in";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "the rubble whilst mining Prismarine in";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "the Executive Mine.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Combine with Component A to craft an Interdimensional Key.";
        parent::__construct(VanillaBlocks::LEVER()->asItem(), $customName, $lore, true, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::KEY_COMPONENT_B => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        return new self();
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::KEY_COMPONENT_B, self::KEY_COMPONENT_B);
        return $tag;
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
        if($itemClicked->getId() === VanillaBlocks::CLAY()->asItem()->getId()) {
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith);
            $itemClickedWithAction->getInventory()->addItem((new InterdimensionalKey())->toItem()->setCount(1));
            return false;
        }
        return true;
    }
}