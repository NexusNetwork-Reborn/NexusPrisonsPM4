<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\player\NexusPlayer;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\TextFormat;

class EnchantmentPage extends Interactive {

    const INCREASE = "Increase";

    const DECREASE = "DECREASE";

    const RARITY = "Rarity";

    /** @var int */
    private $rarity;

    /** @var int */
    private $increase;

    /** @var int */
    private $decrease;

    /**
     * EnchantmentPage constructor.
     *
     * @param int $rarity
     * @param int $increase
     * @param int $decrease
     */
    public function __construct(int $rarity, int $increase, int $decrease) {
        /** @var Enchantment $type */
        $name = Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$rarity];
        $customName = TextFormat::RESET . TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$name] . $name . " Page". TextFormat::RESET . TextFormat::GRAY . " (" . TextFormat::GREEN . "$increase%" . TextFormat::GRAY .")";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "+$increase% enchant rate";
        $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "-$decrease% destroy rate";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Drag 'n Drop onto the";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "desired enchantment book.";
        $this->rarity = $rarity;
        $this->increase = $increase;
        $this->decrease = $decrease;
        parent::__construct(VanillaItems::PAPER(), $customName, $lore, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::INCREASE => IntTag::class,
            self::DECREASE => IntTag::class,
            self::RARITY => IntTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $rarity = $tag->getInt(self::RARITY);
        $increase = $tag->getInt(self::INCREASE);
        $decrease = $tag->getInt(self::DECREASE);
        return new self($rarity, $increase, $decrease);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setInt(self::RARITY, $this->rarity);
        $tag->setInt(self::INCREASE, $this->increase);
        $tag->setInt(self::DECREASE, $this->decrease);
        return $tag;
    }

    /**
     * @return int
     */
    public function getRarity(): int {
        return $this->rarity;
    }

    /**
     * @return int
     */
    public function getIncrease(): int {
        return $this->increase;
    }

    /**
     * @return int
     */
    public function getDecrease(): int {
        return $this->decrease;
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
        $increase = $this->getIncrease();
        $decrease = $this->getDecrease();
        if(EnchantmentBook::isInstanceOf($itemClicked)) {
            $book = EnchantmentBook::fromItem($itemClicked);
            $enchantment = $book->getEnchantment()->getType();
            if($enchantment->getRarity() !== $this->getRarity()) {
                $player->playErrorSound();
                return true;
            }
            $success = $book->getSuccess();
            $destroy = $book->getDestroy();
            if($success >= 100 and $destroy <= 0) {
                $player->playErrorSound();
                return true;
            }
            $finalSuccess = min(100, $success + $increase);
            $finalDestroy = max(0, $destroy - $decrease);
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
            $book->setSuccess($finalSuccess);
            $book->setDestroy($finalDestroy);
            $itemClickedAction->getInventory()->addItem($book->toItem());
            $player->playDingSound();
            return false;
        }
        return true;
    }
}