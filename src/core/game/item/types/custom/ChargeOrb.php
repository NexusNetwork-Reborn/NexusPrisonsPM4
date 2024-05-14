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
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\TextFormat;

class ChargeOrb extends Interactive {

    const CHARGE = "Charge";

    /** @var int */
    private $charge;

    /**
     * ChargeOrb constructor.
     *
     * @param int $charge
     */
    public function __construct(int $charge) {
        $customName = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "+" . number_format($charge). "%" . TextFormat::AQUA . " Charge Orb";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "This will make your pickaxe gain";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . number_format($charge) . "%" .  TextFormat::GOLD . " more energy";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "This will consume one of your";
        $lore[] = TextFormat::RESET . TextFormat::AQUA . "Charge Orb" .  TextFormat::GOLD . " slots.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Drag this on top of the";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "pickaxe you wish to apply to.";
        $this->charge = $charge;
        parent::__construct(VanillaItems::MAGMA_CREAM(), $customName, $lore, true);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "+" . number_format($this->charge). "%" . TextFormat::AQUA . " Charge Orb";
    }

    /**
     * @return string[]
     */
    public function getLore(): array {
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "This will make your pickaxe gain";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . number_format($this->charge) . "%" .  TextFormat::GOLD . " more energy";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "This will consume one of your";
        $lore[] = TextFormat::RESET . TextFormat::AQUA . "Charge Orb" .  TextFormat::GOLD . " slots.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Drag this on top of the";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "pickaxe you wish to apply to.";
        return $lore;
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::CHARGE => IntTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $charge = $tag->getInt(self::CHARGE);
        return new self($charge);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setInt(self::CHARGE, $this->charge);
        return $tag;
    }

    /**
     * @return int
     */
    public function getCharge(): int {
        return $this->charge;
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
        $charge = $this->getCharge();
        if($itemClicked instanceof Pickaxe) {
            if($itemClicked->getChargesUsed() < $itemClicked->getChargeSlots()) {
                $itemClickedAction->getInventory()->removeItem($itemClicked);
                $itemClickedAction->getInventory()->addItem($itemClicked->addCharge($charge));
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