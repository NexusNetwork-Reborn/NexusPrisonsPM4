<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\event\ApplyItemEvent;
use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Pickaxe;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class Absorber extends Interactive {

    const ABSORBER = "Absorber";

    /**
     * Absorber constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Absorber";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Removes Energy from an item";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "without any fee!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Drag 'n Drop on top of";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "desired item";
        parent::__construct(VanillaBlocks::SPONGE()->asItem(), $customName, $lore, true, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::ABSORBER => StringTag::class
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
        $tag->setString(self::ABSORBER, self::ABSORBER);
        return $tag;
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
        if(Energy::isInstanceOf($itemClicked)) {
            return true;
        }
        if($itemClicked instanceof Pickaxe) {
            $energy = $itemClicked->getMaxSubtractableEnergy();
            if($energy <= 0) {
                return true;
            }
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedAction->getInventory()->addItem($itemClicked->subtractEnergy($energy));
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
            $itemClickedWithAction->getInventory()->addItem((new Energy($energy))->toItem());
            $event = new ApplyItemEvent($player, $this);
            $event->call();
            return false;
        }
        if(Satchel::isInstanceOf($itemClicked)) {
            $satchel = Satchel::fromItem($itemClicked);
            $energy = $satchel->getMaxSubtractableEnergy();
            if($energy <= 0) {
                return true;
            }
            $satchel->setEnergy($satchel->getEnergy() - $energy);
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedAction->getInventory()->addItem($satchel->toItem());
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
            $itemClickedWithAction->getInventory()->addItem((new Energy($energy))->toItem());
            $event = new ApplyItemEvent($player, $this);
            $event->call();
            return false;
        }
        if(EnchantmentBook::isInstanceOf($itemClicked)) {
            $book = EnchantmentBook::fromItem($itemClicked);
            $energy = $book->getEnergy();
            if($energy <= 0) {
                return true;
            }
            $book->setEnergy(0);
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedAction->getInventory()->addItem($book->toItem());
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
            $itemClickedWithAction->getInventory()->addItem((new Energy($energy))->toItem());
            $event = new ApplyItemEvent($player, $this);
            $event->call();
            return false;
        }
        return true;
    }
}