<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\game\item\types\task\ContrabandAnimationTask;
use core\game\item\types\task\LootboxAnimationTask;
use core\game\item\types\vanilla\Armor;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\AnvilUseSound;

class Mask extends Interactive {

    const MASK = "Mask";

    /** @var Mask */
    private $mask;

    /**
     * Mask constructor.
     *
     * @param string $mask
     * @param string|null $uuid
     */
    public function __construct(string $mask, ?string $uuid = null) {
        $mask = Nexus::getInstance()->getGameManager()->getItemManager()->getMask($mask);
        $this->mask = $mask;
        $customName = $mask->getColoredName();
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . wordwrap($mask->getDescription(), 25, "\n");
        $lore[] = "";
        foreach($mask->getAbilities() as $ability) {
            $lore[] = TextFormat::RESET . TextFormat::RED . $ability;
        }
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . TextFormat::ITALIC . "Attach this mask to any helmet";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . TextFormat::ITALIC . "to give it a visual override!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "To equip, place this mask on a helmet.";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "To remove, use /rmmask while holding the attached helmet.";
        parent::__construct(VanillaItems::SKELETON_SKULL(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::MASK => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $mask = $tag->getString(self::MASK);
        $uuid = $tag->getString(self::UUID);
        return new self($mask, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::MASK, $this->mask->getName());
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return Mask
     */
    public function getMask(): Mask {
        return $this->mask;
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
        if($itemClicked instanceof Armor) {
            if($itemClicked->getArmorSlot() !== Armor::SLOT_HEAD) {
                $player->playErrorSound();
                return true;
            }
            $masks = $itemClicked->getMasks();
            if(!empty($masks)) {
                $player->playErrorSound();
                return true;
            }
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClicked->setMasks([$this->mask]);
            $itemClickedAction->getInventory()->addItem($itemClicked);
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
            $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound(), [$player]);
            return false;
        }
        return true;
    }
}