<?php

namespace core\game\auction\inventory;

use core\game\auction\AuctionEntry;
use core\game\auction\forms\AuctionSearchForm;
use core\Nexus;
use core\player\NexusPlayer;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\item\Durable;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class AuctionPageInventory extends InvMenu {

    /** @var int */
    private $page;

    /** @var AuctionEntry[][] */
    private $entries;

    /**
     * AuctionPageInventory constructor.
     *
     * @param int $page
     */
    public function __construct(int $page = 1) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->page = $page;
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Auction House");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $action = $transaction->getAction();
            $player = $transaction->getPlayer();
            $itemClicked = $transaction->getItemClicked();
            $slot = $action->getSlot();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($slot === 0) {
                $player->removeCurrentWindow();
                $player->sendDelayedForm(new AuctionSearchForm());
            }
            if($slot === 8) {
                $player->removeCurrentWindow();
                $player->sendDelayedWindow(new AuctionOffersInventory($player));
            }
            if($slot === 47 and $this->page > 1) {
                --$this->page;
                $this->initItems();
            }
            if($slot >= 9 and $slot <= 44) {
                if($itemClicked instanceof Durable) {
                    $identifier = $itemClicked->getId() . ":0";
                }
                else {
                    $identifier = $itemClicked->getId() . ":" . $itemClicked->getMeta();
                }
                if(isset($this->entries[$identifier])) {
                    $entries = $this->entries[$identifier];
                    foreach($entries as $id => $entry) {
                        if(!$entry->isRunning()) {
                            unset($entries[$id]);
                        }
                    }
                    if(!empty($entries)) {
                        $player->removeCurrentWindow();
                        $player->sendDelayedWindow(new AuctionListInventory($entries, 1));
                    }
                    else {
                        $player->playErrorSound();
                        $this->initItems();
                        foreach($this->getInventory()->getViewers() as $viewer) {
                            if($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                                $viewer->getNetworkSession()->getInvManager()->syncContents($this->getInventory());
                            }
                        }
                    }
                }
            }
            if($slot === 51 and $this->page < Nexus::getInstance()->getGameManager()->getAuctionManager()->getMaxPages()) {
                ++$this->page;
                $this->initItems();
            }
            return;
        }));
        $this->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {
        });
    }

    public function initItems(): void {
        $this->entries = Nexus::getInstance()->getGameManager()->getAuctionManager()->getPage($this->page);
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 7, 1);
        $glass->setCustomName(" ");
        $whiteGlass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 0, 1);
        $whiteGlass->setCustomName(" ");
        for($i = 0; $i < 9; $i++) {
            if($i === 0) {
                $offers = ItemFactory::getInstance()->get(ItemIds::COMPASS, 0, 1);
                $offers->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Search");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Search for a certain key";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "word within entries.";
                $offers->setLore($lore);
                $this->getInventory()->setItem($i, $offers);
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
        foreach($this->entries as $itemInfo => $entryCategory) {
            $parts = explode(":", $itemInfo);
            $count = 0;
            $availableCount = 0;
            $lowest = null;
            foreach($entryCategory as $entry) {
                $count += $entry->getItem()->getCount();
                if($lowest === null) {
                    $lowest = $entry->getBuyPrice();
                    $availableCount = $entry->getItem()->getCount();
                }
                elseif($lowest > $entry->getBuyPrice()) {
                    $lowest = $entry->getBuyPrice();
                    $availableCount = $entry->getItem()->getCount();
                }
            }
            $item = ItemFactory::getInstance()->get($parts[0], $parts[1], 1);
            $lore = [];
            $lore[] = "";
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "For sale: " . TextFormat::WHITE . TextFormat::BOLD . number_format($count);
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "Lowest price(ea): " . TextFormat::GREEN . "$" . number_format($lowest, 2);
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "Available: " . TextFormat::GOLD . number_format($availableCount);
            $item->setLore($lore);
            $this->getInventory()->setItem($i++, $item);
        }
        for($i = (9 + count($this->entries)); $i < 54; $i++) {
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
            if($i === 51 and $this->page < Nexus::getInstance()->getGameManager()->getAuctionManager()->getMaxPages()) {
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