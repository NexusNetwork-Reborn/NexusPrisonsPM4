<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\player\NexusPlayer;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\TextFormat;

class EnchantmentDust extends Interactive {

    const ADD_SUCCESS = "AddSuccess";

    /** @var int */
    private $success;

    /**
     * EnchantmentDust constructor.
     *
     * @param int $success
     */
    public function __construct(int $success) {
        $customName = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "Enchantment Dust" . TextFormat::AQUA . TextFormat::RESET . TextFormat::GRAY . " (" . TextFormat::GREEN . "$success%" . TextFormat::GRAY .")";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "+$success% " . TextFormat::RESET . TextFormat::LIGHT_PURPLE . "boost to Pickaxe Enchant Orbs";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . "and the Wormhole.";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::GRAY . "(ADDITIVE)";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Pickaxe Enchant Orbs: Drag 'n Drop";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Wormhole: Drop into portal";
        $this->success = $success;
        parent::__construct(VanillaItems::GLOWSTONE_DUST(), $customName, $lore);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::ADD_SUCCESS => IntTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return BlackScroll
     */
    public static function fromItem(Item $item): EnchantmentDust {
        $tag = self::getCustomTag($item);
        $success = $tag->getInt(self::ADD_SUCCESS);
        return new EnchantmentDust($success);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setInt(self::ADD_SUCCESS, $this->success);
        return $tag;
    }

    /**
     * @return int
     */
    public function getSuccess(): int {
        return $this->success;
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
        $add = $this->getSuccess();
        if(EnchantmentOrb::isInstanceOf($itemClicked)) {
            $orb = EnchantmentOrb::fromItem($itemClicked);
            $enchantment = $orb->getEnchantment();
            $success = $orb->getSuccess();
            if($success >= 100) {
                $player->playErrorSound();
                return true;
            }
            $finalSuccess = min(100, $success + $add);
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
            $itemClickedAction->getInventory()->addItem((new EnchantmentOrb($enchantment, $finalSuccess))->toItem());
            $player->playDingSound();
            return false;
        }
        return true;
    }
}