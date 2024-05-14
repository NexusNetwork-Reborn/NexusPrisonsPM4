<?php

namespace core\game\auction\inventory;

use core\game\auction\AuctionEntry;
use core\game\auction\task\TickListInventory;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class AuctionListInventory extends InvMenu {

    /** @var AuctionEntry[] */
    private $entries;

    /** @var AuctionEntry[] */
    private $entryIndexes = [];

    /** @var int */
    private $page;

    /**
     * AuctionListInventory constructor.
     *
     * @param array $entries
     * @param int $page
     */
    public function __construct(array $entries, int $page = 1) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->entries = $entries;
        $this->page = $page;
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Auction House");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $action = $transaction->getAction();
            $slot = $action->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($slot === 4) {
                $player->removeCurrentWindow();
                $player->sendDelayedWindow(new AuctionPageInventory());
            }
            if($slot === 8) {
                $player->removeCurrentWindow();
                $player->sendDelayedWindow(new AuctionOffersInventory($player));
            }
            if($slot === 47 and $this->page > 1) {
                --$this->page;
            }
            if($slot >= 9 and $slot <= 44) {
                if(isset($this->entryIndexes[$slot])) {
                    $entry = $this->entryIndexes[$slot];
                    if($entry->isRunning()) {
                        $player->removeCurrentWindow();
                        $player->sendDelayedWindow(new AuctionConfirmationInventory($entry));
                    }
                }
            }
            if($slot === 51 and $this->page < ceil(count($this->entries) / 36)) {
                ++$this->page;
            }
            return;
        }));
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TickListInventory($this), 20);
    }

    public function initItems(): void {
        $entries = $this->getPageItems();
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 7, 1);
        $glass->setCustomName(" ");
        $whiteGlass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 0, 1);
        $whiteGlass->setCustomName(" ");
        for($i = 0; $i < 9; $i++) {
            if($i === 4) {
                $home = ItemFactory::getInstance()->get(ItemIds::OAK_DOOR, 0, 1);
                $home->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Home");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Return to the main";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "auction page";
                $home->setLore($lore);
                $this->getInventory()->setItem($i, $home);
                continue;
            }
            if($i === 8) {
                $offers = ItemFactory::getInstance()->get(ItemIds::BOOK, 0, 1);
                $offers->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Offers");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Check your current items";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "that are being sold";
                $offers->setLore($lore);
                $this->getInventory()->setItem($i, $offers);
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
        $i = 9;
        foreach($entries as $entry) {
            $item = clone $entry->getItem();
            $price = $entry->getBuyPrice();
            $availableCount = $item->getCount();
            $seller = $entry->getSeller();
            $time = AuctionEntry::MAX_TIME - (time() - $entry->getStartTime());
            $lore = $item->getLore();
            if($time > 0) {
                $lore[] = "";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Price(ea): " . TextFormat::GREEN . "$" . number_format($price, 2);
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Available: " . TextFormat::GOLD . number_format($availableCount);
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Expires: " . TextFormat::GOLD . Utils::secondsToTime($time);
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Seller: " . TextFormat::DARK_RED . $seller;
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Click to buy this item";
            }
            else {
                $lore[] = "";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "EXPIRED";
            }
            $item->setLore($lore);
            $this->entryIndexes[$i] = $entry;
            $this->getInventory()->setItem($i, $item);
            ++$i;
        }
        for($i = (9 + count($entries)); $i < 54; $i++) {
            if($i === 47 and $this->page > 1) {
                $prevPage = $this->page - 1;
                $pageSelector = ItemFactory::getInstance()->get(ItemIds::REDSTONE_BLOCK, 0, 1);
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "<  Previous page ($prevPage)");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            if($i === 49) {
                $page = ItemFactory::getInstance()->get(ItemIds::PAPER, 0, $this->page);
                $page->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Page $this->page");
                $this->getInventory()->setItem($i, $page);
                continue;
            }
            if($i === 51 and $this->page < $this->getMaxPages()) {
                $nextPage = $this->page + 1;
                $pageSelector = ItemFactory::getInstance()->get(ItemIds::REDSTONE_BLOCK, 0, 1);
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Next page ($nextPage)  >");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            if($i < 45) {
                $this->getInventory()->setItem($i, $whiteGlass);
            }
            else {
                $this->getInventory()->setItem($i, $glass);
            }
        }
    }

    public function tick(): bool {
        $entries = $this->getPageItems($this->page);
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 7, 1);
        $glass->setCustomName(" ");
        $whiteGlass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 0, 1);
        $whiteGlass->setCustomName(" ");
        $i = 9;
        foreach($entries as $entry) {
            $item = clone $entry->getItem();
            $price = $entry->getBuyPrice();
            $availableCount = $item->getCount();
            $seller = $entry->getSeller();
            $time = AuctionEntry::MAX_TIME - (time() - $entry->getStartTime());
            $lore = $item->getLore();
            if($time > 0) {
                $lore[] = "";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Price(ea): " . TextFormat::GREEN . "$" . number_format($price, 2);
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Available: " . TextFormat::GOLD . number_format($availableCount);
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Expires: " . TextFormat::GOLD . Utils::secondsToTime($time);
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Seller: " . TextFormat::DARK_RED . $seller;
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Click to buy this item";
            }
            else {
                $lore[] = "";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "EXPIRED";
            }
            $item->setLore($lore);
            $this->entryIndexes[$i] = $entry;
            $this->getInventory()->setItem($i, $item);
            ++$i;
        }
        for($i = (9 + count($entries)); $i < 54; $i++) {
            if($i === 47 and $this->page > 1) {
                $prevPage = $this->page - 1;
                $pageSelector = ItemFactory::getInstance()->get(ItemIds::REDSTONE_BLOCK, 0, 1);
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "<  Previous page ($prevPage)");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            if($i === 49) {
                $page = ItemFactory::getInstance()->get(ItemIds::PAPER, 0, $this->page);
                $page->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Page $this->page");
                $this->getInventory()->setItem($i, $page);
                continue;
            }
            if($i === 51 and $this->page < $this->getMaxPages()) {
                $nextPage = $this->page + 1;
                $pageSelector = ItemFactory::getInstance()->get(ItemIds::REDSTONE_BLOCK, 0, 1);
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Next page ($nextPage)  >");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            if($i < 45) {
                $this->getInventory()->setItem($i, $whiteGlass);
            }
            else {
                $this->getInventory()->setItem($i, $glass);
            }
        }
        foreach($this->getInventory()->getViewers() as $viewer) {
            if($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                $viewer->getNetworkSession()->getInvManager()->syncContents($this->getInventory());
            }
        }
        return true;
    }

    /**
     * @param int $page
     *
     * @return AuctionEntry[]
     */
    public function getPageItems(int $page = 1): array {
        $sorted = [];
        foreach($this->entries as $entry) {
            if(!$entry->isRunning()) {
                continue;
            }
            $sorted[] = $entry;
        }
        return array_chunk($sorted, 36, true)[$page - 1] ?? [];
    }

    /**
     * @return int
     */
    public function getMaxPages(): int {
        $sorted = [];
        foreach($this->entries as $entry) {
            if(!$entry->isRunning()) {
                continue;
            }
            $sorted[] = $entry;
        }
        return ceil(count($sorted) / 36);
    }

    /**
     * @return int
     */
    public function getPage(): int {
        return $this->page;
    }

    /**
     * @return AuctionEntry[][]
     */
    public function getEntries(): array {
        return $this->entries;
    }
}