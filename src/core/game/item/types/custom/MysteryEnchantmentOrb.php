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
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class MysteryEnchantmentOrb extends Interactive {

    const ORB_RARITY = "OrbRarity";

    /** @var int */
    private $rarity;

    /**
     * MysteryEnchantmentOrb constructor.
     *
     * @param int $rarity
     */
    public function __construct(int $rarity) {
        $rarityName = Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$rarity];
        $color = Rarity::RARITY_TO_COLOR_MAP[$rarityName];
        $customName = TextFormat::RESET . $color . TextFormat::BOLD . "$rarityName Enchantment";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Infused with an unknown energy";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "that needs to be released for";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "it to be useful";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::RED . "Ensure you have free space!";
        $this->rarity = $rarity;
        parent::__construct(ItemFactory::getInstance()->get(ItemIds::DYE, EnchantmentOrb::RARITY_TO_DAMAGE_MAP[$rarity]), $customName, $lore);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::ORB_RARITY => ShortTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $rarity = $tag->getShort(self::ORB_RARITY);
        return new self($rarity);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setShort(self::ORB_RARITY, $this->rarity);
        return $tag;
    }

    /**
     * @return int
     */
    public function getRarity(): int {
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
        $color = Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$rarity]];
        $add = 0;
        if($player->isLoaded()) {
            $add = $player->getDataSession()->getRank()->getBooster() * 100;
        }
        $enchantment = EnchantmentManager::getRandomMiningEnchantment($r);
        if(!$player->isSneaking()) {
            $max = $enchantment->getMaxLevel() > 1 ? 2 : 1;
            $instance = new EnchantmentInstance($enchantment, mt_rand(1, $max));
            $book = (new EnchantmentOrb($instance, (int)min(100, mt_rand(1, 100) + $add)))->toItem();
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
                $enchantment = EnchantmentManager::getRandomMiningEnchantment($r);
                $max = $enchantment->getMaxLevel() > 1 ? $enchantment->getMaxLevel() : 1;
                $instance = new EnchantmentInstance($enchantment, mt_rand(1, $max));
                $book = (new EnchantmentOrb($instance, (int)min(100, mt_rand(1, 100) + $add)))->toItem();
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