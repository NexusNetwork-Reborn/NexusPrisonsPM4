<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\boop\task\DupeLogTask;
use core\game\item\enchantment\Enchantment;
use core\game\item\ItemManager;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use core\translation\Translation;
use libs\utils\Utils;
use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class XPBottle extends Interactive {

    const XP_AMOUNT = "XPAmount";

    const RARITY = "Rarity";

    /** @var int */
    private $amount;

    /** @var string */
    private $rarity;

    /**
     * XPBottle constructor.
     *
     * @param int $amount
     * @param string $rarity
     * @param string|null $uuid
     */
    public function __construct(int $amount, string $rarity, ?string $uuid = null) {
        /** @var Enchantment $type */
        $customName = TextFormat::RESET . TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "XP Bottle" . TextFormat::RESET . TextFormat::GRAY . " (" . number_format($amount) .")";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Applies " . TextFormat::GREEN . TextFormat::BOLD . "+" . number_format($amount) . TextFormat::RED . " Mining XP";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . "to your character!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Tap anywhere to";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "redeem.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Required Mining Level " . TextFormat::WHITE . TextFormat::BOLD . ItemManager::getLevelToUseRarity($rarity);
        $this->amount = $amount;
        $this->rarity = $rarity;
        parent::__construct(VanillaItems::LEAPING_POTION(), $customName, $lore, true, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::XP_AMOUNT => LongTag::class,
            self::RARITY => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $amount = $tag->getLong(self::XP_AMOUNT);
        $rarity = $tag->getString(self::RARITY);
        $uuid = $tag->getString(self::UUID);
        return new self($amount, $rarity, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setLong(self::XP_AMOUNT, $this->amount);
        $tag->setString(self::RARITY, $this->rarity);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return int
     */
    public function getAmount(): int {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void {
        $this->amount = $amount;
        $this->generateNewUniqueId();
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
        $amount = $this->getAmount();
        $rarity = $this->getRarity();
        $dataSession = $player->getDataSession();
        if(ItemManager::getLevelToUseRarity($rarity) > $dataSession->getTotalXPLevel()) {
            $player->sendTranslatedMessage("noPermission");
            $player->playErrorSound();
            return;
        }
        if($dataSession->getXP() >= RPGManager::getLevelCapXP()) {
            $player->sendMessage(Utils::createPrefix(TextFormat::AQUA, "Mining XP Bottle Limit") . TextFormat::RED . "You've reached the max XP capacity!");
            $player->playErrorSound();
            return;
        }
        if($dataSession->getXP() >= (XPUtils::levelToXP(100) + RPGManager::getPrestigeXP($dataSession->getPrestige()))) {
            $player->sendMessage(Utils::createPrefix(TextFormat::AQUA, "Mining XP Bottle Limit") . TextFormat::RED . "You've reached the max XP capacity!");
            $player->playErrorSound();
            return;
        }
        if($dataSession->getMaxDrinkableXP() <= $dataSession->getXPDrank()) {
            $player->sendMessage(Utils::createPrefix(TextFormat::AQUA, "Mining XP Bottle Limit") . TextFormat::GREEN . "0 " . TextFormat::GOLD . "Mining XP remaining" . TextFormat::GRAY . "(resets in " . Utils::secondsToTime(86400 - (time() - $dataSession->getDrinkXPTime())) . ")");
            $player->playErrorSound();
            return;
        }
        $leftOver = $dataSession->drink($amount);
        if($leftOver > 0) {
            $inventory->setItemInHand((new XPBottle($leftOver, $rarity))->toItem());
            $player->sendMessage(Utils::createPrefix(TextFormat::AQUA, "Mining XP Bottle Limit") . TextFormat::GREEN . "0 " . TextFormat::GOLD . "Mining XP remaining" . TextFormat::GRAY . "(resets in " . Utils::secondsToTime(86400 - (time() - $dataSession->getDrinkXPTime())) . ")");
        }
        else {
            $inventory->setItemInHand($item->setCount($item->getCount() - 1));
            $player->sendMessage(Utils::createPrefix(TextFormat::AQUA, "Mining XP Bottle Limit") . TextFormat::GREEN . number_format($dataSession->getMaxDrinkableXP() - $dataSession->getXPDrank()) . " " . TextFormat::GOLD . "Mining XP remaining" . TextFormat::GRAY . "(resets in " . Utils::secondsToTime(86400 - (time() - $dataSession->getDrinkXPTime())) . ")");
        }
        $player->playDingSound();
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
        if(!self::isInstanceOf($itemClicked)) {
            return true;
        }
        $bottle = self::fromItem($itemClicked);
        $amount = $this->getAmount() + $bottle->getAmount();
        $sourceRarity = $this->getRarity();
        $targetRarity = $bottle->getRarity();
        if($sourceRarity !== $targetRarity) {
            $player->playErrorSound();
            return true;
        }
        $uuid = $bottle->getUniqueId();
        if($uuid !== null and $uuid === $this->getUniqueId()) {
//            $player->kickDelay(Translation::getMessage("kickMessage", [
//                "name" => TextFormat::RED . "BOOP",
//                "reason" => TextFormat::YELLOW . "Possibility of being duped! Please trash this item!"
//            ]));
            Server::getInstance()->getAsyncPool()->submitTaskToWorker(new DupeLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()). " {$player->getName()} has a possibly duped item: " . TextFormat::clean($itemClicked->getName())), 1);
            return true;
        }
        $itemClickedWithAction->getInventory()->removeItem($itemClickedWith);
        $itemClickedAction->getInventory()->removeItem($itemClicked);
        $itemClickedAction->getInventory()->addItem((new XPBottle($amount, $sourceRarity))->toItem());
        return false;
    }
}