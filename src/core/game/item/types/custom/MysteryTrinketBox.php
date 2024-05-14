<?php

declare(strict_types=1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Fireworks;
use core\level\entity\types\FireworksRocket;
use core\game\kit\GodKit;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class MysteryTrinketBox extends Interactive {

    const MYSTERY_TRINKET = "Mystery_Trinket";

    /**
     * MysteryTrinketBox constructor.
     *
     * @param string|null $uuid
     */
    public function __construct(?string $uuid = null) {
        $customName = TextFormat::RESET . TextFormat::YELLOW . TextFormat::OBFUSCATED . "||" . TextFormat::RESET . TextFormat::BOLD . TextFormat::BLUE . " Mystery Trinket Box " . TextFormat::RESET . TextFormat::YELLOW . TextFormat::OBFUSCATED . "||";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BLUE . TextFormat::BOLD . "Contains a random Trinket:";
        $lore[] = TextFormat::RESET . TextFormat::BLUE . TextFormat::BOLD . " * " . TextFormat::LIGHT_PURPLE . "Healing Trinket";
        $lore[] = TextFormat::RESET . TextFormat::BLUE . TextFormat::BOLD . " * " . TextFormat::YELLOW . "Absorption Trinket";
        $lore[] = TextFormat::RESET . TextFormat::BLUE . TextFormat::BOLD . " * " . TextFormat::GRAY . "Grappling Hook Trinket";
        $lore[] = TextFormat::RESET . TextFormat::BLUE . TextFormat::BOLD . " * " . TextFormat::DARK_PURPLE . "Resistance Trinket";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right-click to examine";
        parent::__construct(VanillaBlocks::SEA_LANTERN()->asItem(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::MYSTERY_TRINKET => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $uuid = $tag->getString(self::UUID);
        return new self($uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::MYSTERY_TRINKET, self::MYSTERY_TRINKET);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param CompoundTag $tag
     * @param int $face
     * @param Block $blockClicked
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        if(mt_rand(1, 20) === mt_rand(1, 20)) {
            $trinket = \core\game\item\trinket\Trinket::ABSORPTION_TRINKET;
        }
        else {
            switch(mt_rand(1, 3)) {
                case 1:
                    $trinket = \core\game\item\trinket\Trinket::HEALING_TRINKET;
                    break;
                case 2:
                    $trinket = \core\game\item\trinket\Trinket::RESISTANCE_TRINKET;
                    break;
                default:
                    $trinket = \core\game\item\trinket\Trinket::GRAPPLING_TRINKET;
                    break;
            }
        }
        $player->playDingSound();
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $inventory->addItem((new Trinket($trinket))->toItem()->setCount(1));
        $this->setUsed();;
    }
}