<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class HeroicToken extends Interactive {

    const HEROIC_TOKEN = "HeroicToken";

    /**
     * VaultExpansion constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Heroic Token";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Tokens can be spent";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "on upgrades for your gang";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "via /g upgrades";
        parent::__construct(VanillaItems::PAINTING(), $customName, $lore, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::HEROIC_TOKEN => StringTag::class,
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
        $tag->setString(self::HEROIC_TOKEN, self::HEROIC_TOKEN);
        return $tag;
    }
}