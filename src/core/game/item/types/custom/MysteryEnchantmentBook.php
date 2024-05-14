<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class MysteryEnchantmentBook extends Interactive {

    const BOOK_RARITY = "BookRarity";

    /** @var string */
    private $rarity;

    /**
     * MysteryEnchantmentBook constructor.
     *
     * @param string $rarity
     */
    public function __construct(string $rarity) {
        if($rarity === "Legendary+") {
            $color = TextFormat::DARK_PURPLE;
        }
        else {
            $color = Rarity::RARITY_TO_COLOR_MAP[$rarity];
        }
        $customName = TextFormat::RESET . $color . TextFormat::BOLD . "Mystery $rarity Enchantment";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . $color . "Contains a(n) " . TextFormat::BOLD . $rarity . TextFormat::RESET . $color . " tiered";
        $lore[] = TextFormat::RESET . $color . "combat enchantment.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right-click to examine";
        $this->rarity = $rarity;
        parent::__construct(VanillaItems::BOOK(), $customName, $lore);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::BOOK_RARITY => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $rarity = $tag->getString(self::BOOK_RARITY);
        return new self($rarity);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::BOOK_RARITY, $this->rarity);
        return $tag;
    }

    /**
     * @return string
     */
    public function getRarity(): string {
        return $this->rarity;
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
        $rarity = $r = $this->getRarity();
        if($rarity === "Legendary+") {
            if(mt_rand(1, 60) === 1) {
                $r = Rarity::EXECUTIVE;
            }
            elseif(mt_rand(1, 10) === 1) {
                $r = Rarity::GODLY;
            }
            else {
                $r = Rarity::LEGENDARY;
            }
            $color = TextFormat::DARK_PURPLE;
        }
        else {
            $color = Rarity::RARITY_TO_COLOR_MAP[$r];
        }
        $add = 0;
        if($player->isLoaded()) {
            $add = $player->getDataSession()->getRank()->getBooster() * 100;
        }
        $enchantment = EnchantmentManager::getRandomFightingEnchantment(Rarity::RARITY_TO_ENCHANTMENT_RARITY_MAP[$r]);
        if(!$player->isSneaking()) {
            $max = $enchantment->getMaxLevel() > 1 ? 2 : 1;
            $instance = new EnchantmentInstance($enchantment, mt_rand(1, $max));
            $book = (new EnchantmentBook($instance, (int)min(100, mt_rand(1, 100) + $add), (int)max(0, mt_rand(1, 100) - $add)))->toItem();
            if($inventory->canAddItem($book)) {
                $player->sendMessage(TextFormat::BOLD . $color . " » " . TextFormat::RESET . TextFormat::GRAY . "You've discovered a " . $book->getCustomName());
                $player->playLaunchSound();
                $inventory->setItemInHand($item->setCount($item->getCount() - 1));
                $inventory->addItem($book);
                return;
            }
            else {
                $player->sendAlert(Translation::getMessage("fullInventory"));
                $player->playErrorSound();
                return;
            }
        }
        else {
            $opened = 0;
            $names = [];
            for($i = 0; $i < $item->getCount(); $i++) {
                if($rarity === "Legendary+") {
                    if(mt_rand(1, 60) === 1) {
                        $r = Rarity::EXECUTIVE;
                    }
                    elseif(mt_rand(1, 10) === 1) {
                        $r = Rarity::GODLY;
                    }
                    else {
                        $r = Rarity::LEGENDARY;
                    }
                }
                $enchantment = EnchantmentManager::getRandomFightingEnchantment(Rarity::RARITY_TO_ENCHANTMENT_RARITY_MAP[$r]);
                $max = $enchantment->getMaxLevel() > 1 ? $enchantment->getMaxLevel() : 1;
                $instance = new EnchantmentInstance($enchantment, mt_rand(1, $max));
                $book = (new EnchantmentBook($instance, (int)min(100, mt_rand(1, 100) + $add), (int)max(0, mt_rand(1, 100) - $add)))->toItem();
                $names[] = $book->getCustomName();
                if($inventory->canAddItem($book)) {
                    ++$opened;
                    $inventory->addItem($book);
                }
                else {
                    $player->sendAlert(Translation::getMessage("fullInventory"));
                    $player->playErrorSound();
                    break;
                }
            }
            if($opened > 0) {
                $player->playLaunchSound();
                $player->sendMessage(TextFormat::BOLD . $color . " » " . TextFormat::RESET . TextFormat::GRAY . "You've discovered: " . implode(TextFormat::RESET . TextFormat::WHITE . ", ", $names));
                $inventory->setItemInHand($item->setCount($item->getCount() - $opened));
            }
        }
    }
}