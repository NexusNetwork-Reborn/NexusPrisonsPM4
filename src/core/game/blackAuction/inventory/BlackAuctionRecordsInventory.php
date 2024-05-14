<?php

namespace core\game\blackAuction\inventory;

use core\command\forms\ItemInformationForm;
use core\game\auction\AuctionEntry;
use core\game\auction\task\TickListInventory;
use core\game\blackAuction\BlackAuctionRecord;
use core\game\blackAuction\task\TickRecordsInventory;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class BlackAuctionRecordsInventory extends InvMenu {

    /** @var BlackAuctionRecord[] */
    private $entries;

    /** @var BlackAuctionRecord[] */
    private $entryIndexes = [];

    /** @var int */
    private $page;

    /**
     * BlackAuctionRecordsInventory constructor.
     *
     * @param array $entries
     * @param int $page
     */
    public function __construct(array $entries, int $page = 1) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->entries = $entries;
        $this->page = $page;
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "Black Market Auction");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $action = $transaction->getAction();
            $slot = $action->getSlot();
            $player = $transaction->getPlayer();
            $itemClicked = $transaction->getItemClicked();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($slot === 0) {
                $player->removeCurrentWindow();
                $player->sendDelayedWindow(new BlackAuctionMainInventory());
            }
            if($slot === 47 and $this->page > 1) {
                --$this->page;
            }
            if($slot >= 9 and $slot <= 44) {
                $player->removeCurrentWindow();
                $player->sendDelayedForm(new ItemInformationForm($itemClicked));
            }
            if($slot === 51 and $this->page < ceil(count($this->entries) / 36)) {
                ++$this->page;
            }
            return;
        }));
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TickRecordsInventory($this), 20);
    }

    public function initItems(): void {
        $entries = $this->getPageItems();
        $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
        $glass->setCustomName(" ");
        $whiteGlass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::WHITE())->asItem();
        $whiteGlass->setCustomName(" ");
        for($i = 0; $i < 9; $i++) {
            if($i === 0) {
                $home = VanillaItems::REDSTONE_DUST();
                $home->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "<    Back");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Black Market Auction";
                $home->setLore($lore);
                $this->getInventory()->setItem($i, $home);
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
        $i = 9;
        foreach($entries as $entry) {
            $item = clone $entry->getItem();
            $price = $entry->getBuyPrice();
            $buyer = $entry->getBuyer();
            $time = Utils::secondsToTime(time() - $entry->getSoldTime());
            $lore = $item->getLore();
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "------------------------------";
            $lore[] = TextFormat::RESET . TextFormat::GOLD . "Buyer: " . TextFormat::YELLOW . $buyer;
            $lore[] = TextFormat::RESET . TextFormat::GOLD . "Price: " . TextFormat::YELLOW . "$" . number_format($price);
            $lore[] = TextFormat::RESET . TextFormat::GOLD . "Item sold " . TextFormat::BOLD . $time . TextFormat::RESET . TextFormat::GOLD . " ago";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "------------------------------";
            $item->setLore($lore);
            $this->entryIndexes[$i] = $entry;
            $this->getInventory()->setItem($i, $item);
            ++$i;
        }
        for($i = (9 + count($entries)); $i < 54; $i++) {
            if($i === 47 and $this->page > 1) {
                $prevPage = $this->page - 1;
                $pageSelector = VanillaBlocks::REDSTONE()->asItem();
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "<  Previous page ($prevPage)");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            if($i === 49) {
                $page = VanillaItems::PAPER();
                $page->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Page $this->page");
                $this->getInventory()->setItem($i, $page);
                continue;
            }
            if($i === 51 and $this->page < $this->getMaxPages()) {
                $nextPage = $this->page + 1;
                $pageSelector = VanillaBlocks::REDSTONE()->asItem();
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
        $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
        $glass->setCustomName(" ");
        $whiteGlass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::WHITE())->asItem();
        $whiteGlass->setCustomName(" ");
        $i = 9;
        foreach($entries as $entry) {
            $item = clone $entry->getItem();
            $price = $entry->getBuyPrice();
            $buyer = $entry->getBuyer();
            $time = Utils::secondsToTime(time() - $entry->getSoldTime());
            $lore = $item->getLore();
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "------------------------------";
            $lore[] = TextFormat::RESET . TextFormat::GOLD . "Buyer: " . TextFormat::YELLOW . $buyer;
            $lore[] = TextFormat::RESET . TextFormat::GOLD . "Price: " . TextFormat::YELLOW . "$" . number_format($price);
            $lore[] = TextFormat::RESET . TextFormat::GOLD . "Item sold " . TextFormat::BOLD . $time . TextFormat::RESET . TextFormat::GOLD . " ago";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "------------------------------";
            $item->setLore($lore);
            $this->entryIndexes[$i] = $entry;
            $this->getInventory()->setItem($i, $item);
            ++$i;
        }
        for($i = (9 + count($entries)); $i < 54; $i++) {
            if($i === 47 and $this->page > 1) {
                $prevPage = $this->page - 1;
                $pageSelector = VanillaBlocks::REDSTONE()->asItem();
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "<  Previous page ($prevPage)");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            if($i === 49) {
                $page = VanillaItems::PAPER();
                $page->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Page $this->page");
                $this->getInventory()->setItem($i, $page);
                continue;
            }
            if($i === 51 and $this->page < $this->getMaxPages()) {
                $nextPage = $this->page + 1;
                $pageSelector = VanillaBlocks::REDSTONE()->asItem();
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
     * @return BlackAuctionRecord[]
     */
    public function getPageItems(int $page = 1): array {
        $sorted = [];
        foreach($this->entries as $entry) {
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