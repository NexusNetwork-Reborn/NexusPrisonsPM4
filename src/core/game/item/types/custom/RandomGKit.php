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

class RandomGKit extends Interactive {

    const RANDOM_GKIT = "Random_GKit";

    /**
     * RandomGKit constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . TextFormat::OBFUSCATED . "00" . TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . " Random G-Kit Beacon " . TextFormat::OBFUSCATED . TextFormat::GOLD . "00";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Contains a random permanent /gkit:";
        $lore[] = "";
        $kits = Nexus::getInstance()->getGameManager()->getKitManager()->getGodKits();
        foreach($kits as $kit) {
            $lore[] = TextFormat::RESET . $kit->getColoredName();
        }
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right-click to reveal!";
        parent::__construct(VanillaBlocks::BEACON()->asItem(), $customName, $lore, false);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::RANDOM_GKIT => StringTag::class
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
        $tag->setString(self::RANDOM_GKIT, self::RANDOM_GKIT);
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
        $kits = Nexus::getInstance()->getGameManager()->getKitManager()->getGodKits();
        $kit = $kits[array_rand($kits)];
        $player->playDingSound();
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $inventory->addItem((new GKitBeacon($kit))->toItem()->setCount(1));
    }
}