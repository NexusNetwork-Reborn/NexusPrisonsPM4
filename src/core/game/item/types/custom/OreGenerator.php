<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\ItemException;
use core\game\item\ItemManager;
use core\game\item\types\CustomItem;
use core\level\block\Ore;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class OreGenerator extends CustomItem {

    const ORE_GENERATOR = "OreGenerator";

    const ORE = "Ore";

    /** @var Block */
    private $ore;

    /**
     * OreGenerator constructor.
     *
     * @param Block $ore
     *
     * @throws ItemException
     */
    public function __construct(Block $ore) {
        $name = $ore->getName();
        switch($ore->getId()) {
            case BlockLegacyIds::EMERALD_ORE:
                $id = ItemIds::EMERALD_BLOCK;
                $color = TextFormat::GREEN;
                break;
            case BlockLegacyIds::DIAMOND_ORE:
                $id = ItemIds::DIAMOND_BLOCK;
                $color = TextFormat::AQUA;
                break;
            case BlockLegacyIds::GOLD_ORE:
                $id = ItemIds::GOLD_BLOCK;
                $color = TextFormat::YELLOW;
                break;
            case BlockLegacyIds::REDSTONE_ORE:
                $id = ItemIds::REDSTONE_BLOCK;
                $color = TextFormat::RED;
                break;
            case BlockLegacyIds::LAPIS_ORE:
                $id = ItemIds::LAPIS_BLOCK;
                $color = TextFormat::DARK_BLUE;
                break;
            case BlockLegacyIds::IRON_ORE:
                $id = ItemIds::IRON_BLOCK;
                $color = TextFormat::GRAY;
                break;
            case BlockLegacyIds::COAL_ORE:
                $id = ItemIds::COAL_BLOCK;
                $color = TextFormat::DARK_GRAY;
                break;
            default:
                $color = TextFormat::LIGHT_PURPLE;

        }
        $customName = TextFormat::RESET . TextFormat::BOLD . $color . "$name Generator";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Generation Per Minute: " . TextFormat::GREEN . TextFormat::BOLD . \core\level\tile\OreGenerator::getAmountGenerated($ore->getId());
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Spawn Range: " . TextFormat::GREEN . TextFormat::BOLD . \core\level\block\CrudeOre::getRangeByOre($ore->getId()) . " meters";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Hint: Place this in your cell around";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "Crude Ore" . TextFormat::RESET . TextFormat::WHITE . " to generate " . TextFormat::BOLD . $color . $name;
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Required Mining Level " . TextFormat::WHITE . TextFormat::BOLD . ItemManager::getLevelToMineOre($ore);
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(or Prestige " . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "<" . TextFormat::AQUA . "I" . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . TextFormat::GRAY . " to buy & place)";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "-33% XP gain";
        $this->ore = $ore;
        parent::__construct(ItemFactory::getInstance()->get($id), $customName, $lore);
    }
    /**
     * @return array|string[]
     */
    public static function getRequiredTags(): array {
        return [
            self::ORE_GENERATOR => StringTag::class,
            self::ORE => IntTag::class
        ];
    }


    /**
     * @param Item $item
     *
     * @return static
     * @throws ItemException
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $block = BlockFactory::getInstance()->get($tag->getInt(self::ORE), 0);
        return new self($block);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::ORE_GENERATOR, self::ORE_GENERATOR);
        $tag->setInt(self::ORE, $this->ore->getId());
        return $tag;
    }

    /**
     * @return Block
     */
    public function getOre(): Block {
        return $this->ore;
    }
}