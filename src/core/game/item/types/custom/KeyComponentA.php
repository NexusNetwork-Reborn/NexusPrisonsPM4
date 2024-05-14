<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\CustomItem;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class KeyComponentA extends CustomItem {

    const KEY_COMPONENT_A = "KeyComponentA";

    /**
     * Absorber constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Key Component A";
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Soul Essence extracted from an Enderman";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "found in the Executive Mine.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Combine with Component B to craft an Interdimensional Key.";
        parent::__construct(VanillaBlocks::CLAY()->asItem(), $customName, $lore, true, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::KEY_COMPONENT_A => StringTag::class
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
        $tag->setString(self::KEY_COMPONENT_A, self::KEY_COMPONENT_A);
        return $tag;
    }
}