<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class EnchantmentReroll extends Interactive {

    const ENCHANT_REROLL = "EnchantReroll";

    /**
     * EnchantmentReroll constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Enchant Re-Roll";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Re-rolls the random enchants";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "you get from the wormhole.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Drop this into the wormhole to apply this.";
        parent::__construct(VanillaItems::PINK_DYE(), $customName, $lore, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::ENCHANT_REROLL => StringTag::class
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
        $tag->setString(self::ENCHANT_REROLL, self::ENCHANT_REROLL);
        return $tag;
    }
}