<?php

namespace core\game\auction\inventory;

use core\game\auction\AuctionEntry;
use core\game\auction\task\TickOffersInventory;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class AuctionOffersInventory extends InvMenu {

    /** @var NexusPlayer */
    private $owner;

    /** @var AuctionEntry[] */
    private $entries = [];

    /**
     * AuctionOffersInventory constructor.
     *
     * @param NexusPlayer $owner
     */
    public function __construct(NexusPlayer $owner) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->owner = $owner;
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
            if($slot >= 9) {
                if(isset($this->entries[$slot])) {
                    $entry = $this->entries[$slot];
                    $entry->cancel($player);
                    unset($this->entries[$slot]);
                    $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 7, 1);
                    $glass->setCustomName(" ");
                    $this->getInventory()->setItem($slot, $glass);
                }
            }
            return;
        }));
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TickOffersInventory($this), 20);
    }

    public function initItems(): void {
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 7, 1);
        $glass->setCustomName(" ");
        $entries = Nexus::getInstance()->getGameManager()->getAuctionManager()->getEntriesOf($this->owner);
        for($i = 0; $i < 54; $i++) {
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
            if($i >= 9 and (!empty($entries))) {
                $entry = array_shift($entries);
                $this->entries[$i] = $entry;
                $item = clone $entry->getItem();
                $lore = $item->getLore();
                $time = AuctionEntry::MAX_TIME - (time() - $entry->getStartTime());
                if($time > 0) {
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Click to cancel this offer";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Price(ea): " . TextFormat::GREEN . "$" . number_format($entry->getBuyPrice(), 2);
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Remaining: " . TextFormat::GOLD . number_format($item->getCount());
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Expires: " . TextFormat::GOLD . Utils::secondsToTime($time);
                }
                else {
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "EXPIRED";
                }
                $item->setLore($lore);
                $this->getInventory()->setItem($i, $item);
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
    }

    public function tick(): bool {
        $this->entries = [];
        $entries = Nexus::getInstance()->getGameManager()->getAuctionManager()->getEntriesOf($this->owner);
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 7, 1);
        $glass->setCustomName(" ");
        if(empty($entries)) {
            for($i = 9; $i < 54; $i++) {
                $this->getInventory()->setItem($i, $glass);
            }
            return false;
        }
        for($i = 9; $i < 54; $i++) {
            if($i >= 9 and (!empty($entries))) {
                $entry = array_shift($entries);
                $this->entries[$i] = $entry;
                $item = clone $entry->getItem();
                $lore = $item->getLore();
                $time = AuctionEntry::MAX_TIME - (time() - $entry->getStartTime());
                if($time > 0) {
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Click to cancel this offer";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Price(ea): " . TextFormat::GREEN . "$" . number_format($entry->getBuyPrice(), 2);
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Remaining: " . TextFormat::GOLD . number_format($item->getCount());
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Expires: " . TextFormat::GOLD . Utils::secondsToTime($time);
                }
                else {
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "EXPIRED";
                }
                $item->setLore($lore);
                $this->getInventory()->setItem($i, $item);
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
        foreach($this->getInventory()->getViewers() as $viewer) {
            if($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                $viewer->getNetworkSession()->getInvManager()->syncContents($this->getInventory());
            }
        }
        return true;
    }

    /**
     * @return AuctionEntry[][]
     */
    public function getEntries(): array {
        return $this->entries;
    }
}