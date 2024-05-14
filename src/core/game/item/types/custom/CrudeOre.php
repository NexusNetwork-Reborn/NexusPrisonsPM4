<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\ItemManager;
use core\game\item\types\CustomItem;
use core\game\item\types\Rarity;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class CrudeOre extends CustomItem {

    const CRUDE_ORE = "CrudeOre";

    const RARITY = "Rarity";

    const REFINED = "Refined";

    /** @var string */
    private $rarity;

    /** @var bool */
    private $refined;

    /**
     * CrudeOre constructor.
     *
     * @param string $rarity
     * @param bool $refined
     */
    public function __construct(string $rarity, bool $refined = false) {
        if(!$refined) {
            $customName = TextFormat::RESET . TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "$rarity Crude Ore";
        }
        else {
            $customName = TextFormat::RESET . TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "$rarity Refined Crude Ore";
        }
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Generation Rate: " . TextFormat::GREEN . TextFormat::BOLD . Rarity::getCrudeOreMultiplier($rarity) . "x";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Hint: Place this in your cell around";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "Ore Generators" . TextFormat::RESET . TextFormat::WHITE . " to generate " . TextFormat::BOLD . TextFormat::GRAY . "Ore";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Required Mining Level " . TextFormat::WHITE . TextFormat::BOLD . ItemManager::getLevelToUseRarity($rarity);
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(or Prestige " . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "<" . TextFormat::AQUA . "I" . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . TextFormat::GRAY . " to buy & place)";
        $this->rarity = $rarity;
        $this->refined = $refined;
        parent::__construct(VanillaBlocks::NETHER_QUARTZ_ORE()->asItem(), $customName, $lore);
    }

    /**
     * @return array|string[]
     */
    public static function getRequiredTags(): array {
        return [
            self::CRUDE_ORE => StringTag::class,
            self::RARITY => StringTag::class,
            self::REFINED => ByteTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $rarity = $tag->getString(self::RARITY);
        $refined = (bool)$tag->getByte(self::REFINED);
        return new self($rarity, $refined);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::CRUDE_ORE, self::CRUDE_ORE);
        $tag->setString(self::RARITY, $this->rarity);
        $tag->setByte(self::REFINED, (int)$this->refined);
        return $tag;
    }

    /**
     * @return string
     */
    public function getRarity(): string {
        return $this->rarity;
    }

    /**
     * @return bool
     */
    public function isRefined(): bool {
        return $this->refined;
    }
}