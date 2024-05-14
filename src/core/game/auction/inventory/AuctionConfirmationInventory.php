<?php

namespace core\game\auction\inventory;

use core\game\auction\AuctionEntry;
use core\game\auction\task\TickConfirmationInventory;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class AuctionConfirmationInventory extends InvMenu {

    /** @var AuctionEntry */
    private $entry;

    /** @var int */
    private $count = 1;

    /**
     * AuctionConfirmationInventory constructor.
     *
     * @param AuctionEntry $entry
     */
    public function __construct(AuctionEntry $entry) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_HOPPER));
        $this->entry = $entry;
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Auction House");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $action = $transaction->getAction();
            $slot = $action->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $availableCount = $this->entry->getItem()->getCount();
            if($slot === 0) {
                $player->removeCurrentWindow();
                $player->sendDelayedWindow(new AuctionPageInventory());
            }
            if($slot === 1) {
                if($this->count > 1) {
                    --$this->count;
                }
                else {
                    $player->playErrorSound();
                }
            }
            if($slot === 3) {
                if($this->count < $availableCount) {
                    ++$this->count;
                }
                else {
                    $player->playErrorSound();
                }
            }
            if($slot === 4) {
                if($this->entry->getSeller() !== $player->getName()) {
                    if($availableCount < $this->count) {
                        $this->count = $availableCount;
                        $player->playErrorSound();
                    }
                    else {
                        $this->entry->buy($player, $this->count);
                        $player->removeCurrentWindow();
                    }
                }
                else {
                    $player->playErrorSound();
                }
            }
            return;
        }));
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TickConfirmationInventory($this), 20);
    }

    public function initItems(): void {
        $item = clone $this->entry->getItem();
        $price = $this->entry->getBuyPrice();
        $availableCount = $item->getCount();
        $item->setCount($this->count);
        $seller = $this->entry->getSeller();
        $time = AuctionEntry::MAX_TIME - (time() - $this->entry->getStartTime());
        if($time > 0) {
            $additionalLore = [];
            $additionalLore[] = "";
            $additionalLore[] = "";
            $additionalLore[] = TextFormat::RESET . TextFormat::GRAY . "Price(ea): " . TextFormat::GREEN . "$" . number_format($price);
            $additionalLore[] = TextFormat::RESET . TextFormat::GRAY . "Available: " . TextFormat::GOLD . number_format($availableCount);
            $additionalLore[] = TextFormat::RESET . TextFormat::GRAY . "Expires: " . TextFormat::GOLD . Utils::secondsToTime($time);
            $additionalLore[] = "";
            $additionalLore[] = TextFormat::RESET . TextFormat::GRAY . "Count: " . TextFormat::GOLD . number_format($this->count);
            $additionalLore[] = TextFormat::RESET . TextFormat::GRAY . "Cost: " . TextFormat::GREEN . "$" . number_format($this->count * $price);
            $additionalLore[] = "";
            $additionalLore[] = TextFormat::RESET . TextFormat::GRAY . "Seller: " . TextFormat::DARK_RED . $seller;
        }
        else {
            $additionalLore[] = "";
            $additionalLore[] = "";
            $additionalLore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "EXPIRED";
        }
        $cancel = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 14, 1);
        $cancel->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Cancel");
        $this->getInventory()->setItem(0, $cancel->setLore($additionalLore));
        $subtract = ItemFactory::getInstance()->get(ItemIds::IRON_BARS, 0, 1);
        $subtract->setCustomName(TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "-1");
        $this->getInventory()->setItem(1, $subtract->setLore($additionalLore));
        $this->getInventory()->setItem(2, $item->setLore(array_merge($item->getLore(), $additionalLore)));
        $add = ItemFactory::getInstance()->get(ItemIds::IRON_BARS, 0, 1);
        $add->setCustomName(TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "+1");
        $this->getInventory()->setItem(3, $add->setLore($additionalLore));
        $confirm = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 13, 1);
        $confirm->setCustomName(TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Confirm");
        $this->getInventory()->setItem(4, $confirm->setLore($additionalLore));
    }

    public function tick(): bool {
        $this->initItems();
        $viewers = $this->getInventory()->getViewers();
        foreach($viewers as $viewer) {
            if(!$this->entry->isRunning()) {
                $viewer->removeCurrentWindow();
                $viewer->sendDelayedWindow(new AuctionPageInventory());
            }
            if($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                $viewer->getNetworkSession()->getInvManager()->syncContents($this->getInventory());
            }
        }
        return true;
    }

    /**
     * @return AuctionEntry
     */
    public function getEntry(): AuctionEntry {
        return $this->entry;
    }
}