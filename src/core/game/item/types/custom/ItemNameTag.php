<?php

declare(strict_types=1);

namespace core\game\item\types\custom;

use core\command\forms\RenameItemForm;
use core\command\inventory\PrestigeTokenInventory;
use core\command\inventory\RenameItemInventory;
use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class ItemNameTag extends Interactive {

    const ITEM_NAME_TAG = "ItemNameTag";

    /**
     * ItemNameTag constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "Item Name Tag";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right-click to use this";
        parent::__construct(ItemFactory::getInstance()->get(ItemIds::NAME_TAG), $customName, $lore, false);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::ITEM_NAME_TAG => StringTag::class
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
        $tag->setString(self::ITEM_NAME_TAG, self::ITEM_NAME_TAG);
        return $tag;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        $player->sendDelayedWindow(new RenameItemInventory($player, $this->toItem()));
    }
}