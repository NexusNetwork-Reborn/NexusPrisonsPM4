<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Pickaxe;
use core\player\NexusPlayer;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\AnvilUseSound;

class BlackScroll extends Interactive {

    const SUCCESS_TO_RECEIVE = "SuccessToReceive";

    /** @var int */
    private $success;

    /**
     * BlackScroll constructor.
     *
     * @param int $success
     */
    public function __construct(int $success) {
        $success = min(100, $success);
        $customName = TextFormat::RESET . TextFormat::DARK_GRAY . TextFormat::BOLD . "Black Scroll " . TextFormat::RESET . TextFormat::GRAY . "(" . TextFormat::WHITE . "$success%" . TextFormat::GRAY .")";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Extract an Enchantment from a";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . "pickaxe or a piece of gear";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . "The resulting " . TextFormat::AQUA . TextFormat::BOLD . "Enchantment";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . "will have a " .  TextFormat::GREEN . TextFormat::BOLD  . $success . "% " . TextFormat::RESET . TextFormat::LIGHT_PURPLE . "rate of success.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Drag this on top of the pickaxe or";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "a piece of armor to extract a";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "random enchantment.";
        $this->success = $success;
        parent::__construct(VanillaItems::INK_SAC(), $customName, $lore);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return TextFormat::RESET . TextFormat::DARK_GRAY . TextFormat::BOLD . "Black Scroll " . TextFormat::RESET . TextFormat::GRAY . "(" . TextFormat::WHITE . "$this->success%" . TextFormat::GRAY .")";
    }

    /**
     * @return string[]
     */
    public function getLore(): array {
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Extract an Enchantment from a";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . "pickaxe or a piece of gear";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . "The resulting " . TextFormat::AQUA . TextFormat::BOLD . "Enchantment";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . "will have a " .  TextFormat::GREEN . TextFormat::BOLD  . $this->success . "% " . TextFormat::RESET . TextFormat::LIGHT_PURPLE . "rate of success.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Drag this on top of the pickaxe or";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "a piece of armor to extract a";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "random enchantment.";
        return $lore;
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::SUCCESS_TO_RECEIVE => IntTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $success = $tag->getInt(self::SUCCESS_TO_RECEIVE);
        return new self($success);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setInt(self::SUCCESS_TO_RECEIVE, $this->success);
        return $tag;
    }

    /**
     * @return int
     */
    public function getSuccess(): int {
        return min(100, $this->success);
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
        $success = $this->getSuccess();
        $enchantments = array_filter($itemClicked->getEnchantments(), function(EnchantmentInstance $enchantment): ?EnchantmentInstance {
            if($enchantment->getType() instanceof Enchantment) {
                return $enchantment;
            }
            return null;
        });
        /** @var EnchantmentInstance[] $enchantments */
        $enchantments = array_filter($enchantments);
        if(count($enchantments) > 0) {
            $enchantment = $enchantments[array_rand($enchantments)];
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClicked->removeEnchantment($enchantment->getType());
            $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound());
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith->setCount(1));
            if($itemClicked instanceof Pickaxe) {
                $itemClickedAction->getInventory()->addItem($itemClicked);
                $itemClickedWithAction->getInventory()->addItem((new EnchantmentOrb($enchantment, $success))->toItem());
            }
            elseif(Satchel::isInstanceOf($itemClicked)) {
                $satchel = Satchel::fromItem($itemClicked);
                $itemClickedAction->getInventory()->addItem($satchel->toItem());
                $itemClickedWithAction->getInventory()->addItem((new EnchantmentOrb($enchantment, $success))->toItem());
            }
            else {
                $itemClickedAction->getInventory()->addItem($itemClicked);
                $itemClickedWithAction->getInventory()->addItem((new EnchantmentBook($enchantment, $success, mt_rand(1, 100)))->toItem());
            }
            return false;
        }
        return true;
    }
}