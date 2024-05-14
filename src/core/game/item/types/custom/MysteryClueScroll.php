<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\Nexus;
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
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class MysteryClueScroll extends Interactive {

    const SCROLL_RARITY = "ScrollRarity";

    /** @var string */
    private $rarity;

    /**
     * MysteryEnchantmentBook constructor.
     *
     * @param string $rarity
     */
    public function __construct(string $rarity) {
        $color = Rarity::RARITY_TO_COLOR_MAP[$rarity];
        $customName = TextFormat::RESET . $color . TextFormat::BOLD . "Mystery $rarity Clue Scroll";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Complete this Clue Scroll to discover";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "a casket filled with XP, Money, and Items!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Right-click to decode the clue!)";
        $this->rarity = $rarity;
        parent::__construct(ItemFactory::getInstance()->get(ItemIds::EMPTY_MAP), $customName, $lore);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::SCROLL_RARITY => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $rarity = $tag->getString(self::SCROLL_RARITY);
        return new self($rarity);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::SCROLL_RARITY, $this->rarity);
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
        $rarity = $this->getRarity();
        $color = Rarity::RARITY_TO_COLOR_MAP[$rarity];
        $scrollManager = Nexus::getInstance()->getGameManager()->getItemManager()->getScrollManager();
        $challenge = $scrollManager->getRandomChallengeByRarity($rarity);
        $add = (new ClueScroll($rarity, [$challenge->getId()]))->toItem()->setCount(1);
        if(!$inventory->canAddItem($add)) {
            $player->sendAlert(Translation::getMessage("fullInventory"));
            $player->playErrorSound();
            return;
        }
        $player->playLaunchSound();
        $player->playTwinkleSound();
        $player->sendTitle($color . TextFormat::BOLD . "(!) $rarity CLUE SCROLL DECODED!", TextFormat::GRAY . "The first step can be found in your chat!");
        $player->sendMessage(" ");
        $player->sendMessage(TextFormat::RESET . TextFormat::BOLD . $color . "(!) Simple CLUE SCROLL DECODED! First step:");
        $player->sendMessage(TextFormat::RESET . TextFormat::BOLD . $color . "1. " . TextFormat::RESET . TextFormat::WHITE . $challenge->getDescription());
        $player->sendMessage(" ");
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $inventory->addItem($add);
    }
}