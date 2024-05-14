<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\boop\task\DupeLogTask;
use core\game\item\types\Interactive;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MoneyNote extends Interactive {

    const BALANCE = "Balance";

    /** @var int */
    private $balance;

    /**
     * MoneyNote constructor.
     *
     * @param float $amount
     * @param string $withdrawer
     * @param string|null $uuid
     */
    public function __construct(float $amount, string $withdrawer = "Admin", ?string $uuid = null) {
        $customName = TextFormat::RESET . TextFormat::GREEN . "$" . number_format($amount, 2);
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Signed by " . TextFormat::RESET . TextFormat::WHITE . $withdrawer;
        $this->balance = $amount;
        parent::__construct(VanillaItems::PAPER(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::BALANCE => DoubleTag::class
        ];
    }
    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $amount = $tag->getDouble(self::BALANCE);
        $uuid = $tag->getString(self::UUID);
        return new self($amount, "Admin", $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setDouble(self::BALANCE, $this->balance);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return float
     */
    public function getBalance(): float {
        return $this->balance;
    }

    /**
     * @param int $balance
     */
    public function setBalance(float $balance): void {
        $this->balance = $balance;
        $this->generateNewUniqueId();
    }


    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        if(!Nexus::getInstance()->getServerManager()->getAreaManager()->canDamage($player->getPosition())) {
            $amount = $this->getBalance();
            $player->playDingSound();
            $player->getDataSession()->addToBalance($amount);
            $this->setUsed();
            $inventory->setItemInHand($item->setCount($item->getCount() - 1));
            return;
        }
        $player->sendTranslatedMessage("inWarzone");
        $player->playErrorSound();
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
        $note = self::fromItem($itemClicked);
        $itemManager = Nexus::getInstance()->getGameManager()->getItemManager();
        $uuid = $this->getUniqueId();
        if($itemManager->isRedeemed($uuid)) {
//            $player->kickDelay(Translation::getMessage("kickMessage", [
//                "name" => TextFormat::RED . "BOOP",
//                "reason" => TextFormat::YELLOW . "Possibility of being duped! Please trash this item!"
//            ]));
            Server::getInstance()->getAsyncPool()->submitTaskToWorker(new DupeLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()). " {$player->getName()} has a possibly duped item: " . TextFormat::clean($itemClicked->getCustomName())), 1);
//            return true;
        }
        $balance = $this->getBalance() + $note->getBalance();
        $this->setUsed();
        $note->setUsed();
        $itemClickedWithAction->getInventory()->removeItem($itemClickedWith);
        $itemClickedAction->getInventory()->removeItem($itemClicked);
        $itemClickedAction->getInventory()->addItem((new MoneyNote($balance, $player->getName()))->toItem());
        return false;
    }
}