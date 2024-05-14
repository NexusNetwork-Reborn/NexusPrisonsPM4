<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\event\ApplyItemEvent;
use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Pickaxe;
use core\level\LevelException;
use core\player\NexusPlayer;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class ChargeOrbSlot extends Interactive {

    const CHARGE_SLOT = "ChargeSlot";

    /**
     * ChargeOrbSlot constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Charge Orb Slot";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Applying this item to a pickaxe allows";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "you to add " . TextFormat::WHITE . "1 " . TextFormat::GOLD . "additional " . TextFormat::AQUA . "Charge Orb";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Drag this onto the pickaxe you";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "wish to add 1 Charge Orb Slot to!";
        parent::__construct(VanillaItems::HEART_OF_THE_SEA(), $customName, $lore, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::CHARGE_SLOT => StringTag::class
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
        return new CompoundTag();
    }

    /**
     * @param NexusPlayer $player
     * @param Item $itemClickedWith
     * @param Item $itemClicked
     * @param SlotChangeAction $itemClickedWithAction
     * @param SlotChangeAction $itemClickedAction
     *
     * @return bool
     *
     * @throws LevelException
     */
    public function onCombine(NexusPlayer $player, Item $itemClickedWith, Item $itemClicked, SlotChangeAction $itemClickedWithAction, SlotChangeAction $itemClickedAction): bool {
        if($itemClicked instanceof Pickaxe) {
            $max = 10;
            if($itemClicked->getAttribute(Pickaxe::ENERGY_MASTERY) > 0) {
                $max = 10 + $itemClicked->getAttribute(Pickaxe::ENERGY_MASTERY);
            }
            if($itemClicked->getChargeSlots() < $max) {
                $itemClickedAction->getInventory()->removeItem($itemClicked);
                $itemClickedAction->getInventory()->addItem($itemClicked->addChargeSlot());
                $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
                $player->playDingSound();
                $event = new ApplyItemEvent($player, $this);
                $event->call();
                return false;
            }
            $player->playErrorSound();
        }
        return true;
    }
}