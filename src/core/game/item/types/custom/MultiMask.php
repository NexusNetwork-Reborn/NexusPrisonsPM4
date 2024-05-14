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
use libs\utils\ArrayUtils;
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

class MultiMask extends Interactive {

    const MULTI_MASK = "Multi_Mask";

    /** @var Mask[] */
    private $masks;

    /**
     * Mask constructor.
     *
     * @param array $masks
     * @param string|null $uuid
     */
    public function __construct(array $masks, ?string $uuid = null) {
        $names = [];
        foreach($masks as $mask) {
            $mask = Nexus::getInstance()->getGameManager()->getItemManager()->getMask($mask);
            $this->masks[] = $mask;
            $names[] = $mask->getColoredName();
        }
        $customName = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . "Multi-Mask (" . implode(TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . ", ", $names) . TextFormat::BOLD . TextFormat::WHITE . ")";
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "This mask contains the power of:";
        $lore[] = "";
        foreach($this->masks as $mask) {
            $lore[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . " * " . $mask->getColoredName();
            foreach($mask->getAbilities() as $ability) {
                $lore[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . "(" . TextFormat::RESET . TextFormat::RED . $ability . TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . ")";
            }
            $lore[] = "";
        }
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "To equip, place this mask on a helmet.";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "To remove, use /rmmask while holding the attached helmet.";
        parent::__construct(VanillaItems::WITHER_SKELETON_SKULL(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::MULTI_MASK => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $masks = $tag->getString(self::MULTI_MASK);
        $uuid = $tag->getString(self::UUID);
        return new self(ArrayUtils::decodeArray($masks), $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $names = [];
        foreach($this->masks as $mask) {
            $names[] = $mask->getName();
        }
        $tag->setString(self::MULTI_MASK, ArrayUtils::encodeArray($names));
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return Mask[]
     */
    public function getMasks(): array {
        return $this->masks;
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
            $itemClicked->setMasks($this->masks);
            $itemClickedAction->getInventory()->addItem($itemClicked);
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
            $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound(), [$player]);
            return false;
        }
        return true;
    }
}