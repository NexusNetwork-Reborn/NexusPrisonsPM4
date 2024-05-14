<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\CustomItem;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class ForgeFuel extends CustomItem {

    const FORGE_FUEL = "ForgeFuel";

    /**
     * OreGenerator constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Energy Forge Fuel";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Increases the rate of Energy generation";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "in a Cell Energy Forge";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . " * VIP Security Forge" . TextFormat::RESET . TextFormat::GRAY . ": +" . TextFormat::WHITE . "3,000" . TextFormat::GRAY . "/hr";
        $lore[] = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . " * Maximum Security Forge" . TextFormat::RESET . TextFormat::GRAY . ": +" . TextFormat::WHITE . "2,000" . TextFormat::GRAY . "/hr";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * High Security Forge" . TextFormat::RESET . TextFormat::GRAY . ": +" . TextFormat::WHITE . "1,500" . TextFormat::GRAY . "/hr";
        $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . " * Medium Security Forge" . TextFormat::RESET . TextFormat::GRAY . ": +" . TextFormat::WHITE . "1,000" . TextFormat::GRAY . "/hr";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "NOTE: Increasing generation rate requires";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "additional space around the Energy Forge";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Place on a Energy Forge)";
        parent::__construct(VanillaBlocks::OBSIDIAN()->asItem(), $customName, $lore, true);
    }
    /**
     * @return array|string[]
     */
    public static function getRequiredTags(): array {
        return [
            self::FORGE_FUEL => StringTag::class
        ];
    }


    /**
     * @param Item $item
     *
     * @return static
     */
    public static function fromItem(Item $item): self {
        return new self();
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::FORGE_FUEL, self::FORGE_FUEL);
        return $tag;
    }
}