<?php

declare(strict_types=1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class GKitItemGenerator extends Interactive {

    const GKIT_ITEM = "GKitItem";

    /**
     * GKitItemGenerator constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "G-Kit Item Generator";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Generates 1 item from one";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "of the following /gkits:";
        $lore[] = "";
        $kits = Nexus::getInstance()->getGameManager()->getKitManager()->getGodKits();
        foreach($kits as $kit) {
            $lore[] = TextFormat::RESET . $kit->getColoredName();
        }
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right-click to generate!";
        parent::__construct(VanillaBlocks::DARK_PRISMARINE()->asItem(), $customName, $lore, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::GKIT_ITEM => StringTag::class
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
        $tag->setString(self::GKIT_ITEM, self::GKIT_ITEM);
        return $tag;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        $kits = Nexus::getInstance()->getGameManager()->getKitManager()->getGodKits();
        $kit = $kits[array_rand($kits)];
        $items = $kit->giveTo($player, false);
        $give = $items[array_rand($items)];
        $player->playDingSound();
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $inventory->addItem($give);
    }
}