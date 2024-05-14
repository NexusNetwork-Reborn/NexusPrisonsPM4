<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class Token extends Interactive {

    const TOKEN = "Token";

    /**
     * VaultExpansion constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Token";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Gained as a reward for";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "completing " . TextFormat::BOLD . TextFormat::AQUA . "/Quest";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Tokens can be spent on";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "rare items at the Quest Shop";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "which is hidden somewhere on";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "the map and is visible";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "every Friday at 6PM EST";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "to Saturday at 6PM EST";
        parent::__construct(VanillaItems::PRISMARINE_CRYSTALS(), $customName, $lore, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::TOKEN => StringTag::class,
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
        $tag->setString(self::TOKEN, self::TOKEN);
        return $tag;
    }
}