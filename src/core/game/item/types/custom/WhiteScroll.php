<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\event\ApplyItemEvent;
use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class WhiteScroll extends Interactive {

    const WHITESCROLL = "WhiteScroll";

    /**
     * WhiteScroll constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . "White Scroll";
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "A protection scroll";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "When applied to Pickaxes / Trinkets:";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "You keep the Pickaxe upon death";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "but lose the energy on it.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "When applied to Satchels:";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "You keep the Satchel upon death";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "but lose the ores on it.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "When applied to Gear:";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "You keep the item when it would usually";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "be destroyed by an enchantment book.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Drag 'n drop in your inventory";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "onto the item you wish to apply it to.";
        parent::__construct(VanillaItems::PAPER(), $customName, $lore, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::WHITESCROLL => StringTag::class
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
        $tag->setString(self::WHITESCROLL, self::WHITESCROLL);
        return $tag;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param CompoundTag $tag
     * @param int $face
     * @param Block $blockClicked
     *
     * @throws TranslationException
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
    }

    /**
     * @param NexusPlayer $player
     * @param Item $itemClickedWith
     * @param Item $itemClicked
     * @param SlotChangeAction $itemClickedWithAction
     * @param SlotChangeAction $itemClickedAction
     *
     * @return bool
     */
    public function onCombine(NexusPlayer $player, Item $itemClickedWith, Item $itemClicked, SlotChangeAction $itemClickedWithAction, SlotChangeAction $itemClickedAction): bool {
        if($itemClicked instanceof Pickaxe or $itemClicked instanceof Sword or $itemClicked instanceof Armor or $itemClicked instanceof Axe or $itemClicked instanceof Bow) {
            if($itemClicked->isWhitescrolled()) {
                $player->playErrorSound();
                return true;
            }
            $player->playDingSound();
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedAction->getInventory()->addItem($itemClicked->setWhitescrolled(true));
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
            $event = new ApplyItemEvent($player, $this);
            $event->call();
            return false;
        }
        if(Trinket::isInstanceOf($itemClicked)) {
            $trinket = Trinket::fromItem($itemClicked);
            $whitescroll = $trinket->isWhitescroll();
            if($whitescroll) {
                $player->playErrorSound();
                return true;
            }
            if($trinket->getCorrupted() >= 3) {
                $player->playErrorSound();
                $player->sendMessage(Translation::RED . "Your item is corrupted!");
                return true;
            }
            $trinket->setWhitescroll(true);
            $trinket->setCorrupted($trinket->getCorrupted() + 1);
            $player->playDingSound();
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedAction->getInventory()->addItem($trinket->toItem());
            $event = new ApplyItemEvent($player, $this);
            $event->call();
            return false;
        }
        if(Satchel::isInstanceOf($itemClicked)) {
            $satchel = Satchel::fromItem($itemClicked);
            $whitescroll = $satchel->isWhitescroll();
            if($whitescroll) {
                $player->playErrorSound();
                return true;
            }
            $satchel->setWhitescroll(true);
            $player->playDingSound();
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedAction->getInventory()->addItem($satchel->toItem());
            $event = new ApplyItemEvent($player, $this);
            $event->call();
            return false;
        }
        return true;
    }
}