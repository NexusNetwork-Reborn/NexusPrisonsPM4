<?php
declare(strict_types=1);

namespace core\game\blackAuction;

use core\game\fund\FundManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use libs\utils\Utils;
use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class BlackAuctionEntry {

    /** @var int */
    private $bid = 0;

    /** @var null|string */
    private $bidder = null;

    /** @var Item */
    private $item;

    /** @var int */
    private $time;

    /** @var int */
    private $length = 300;

    /**
     * BlackAuctionEntry constructor.
     *
     * @param Item $item
     */
    public function __construct(Item $item) {
        $this->item = $item;
        $this->time = time();
    }

    /**
     * @return string
     */
    public function getItemName(): string {
        return $this->item->hasCustomName() ? $this->item->getCustomName() : $this->item->getName();
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    public function announce(): void {
        if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_SIX)) {
            return;
        }
        Server::getInstance()->broadcastMessage(" ");
        Server::getInstance()->broadcastMessage(Utils::centerAlignText(TextFormat::BOLD . TextFormat::GOLD . "* Black Market Auction *", 50));
        Server::getInstance()->broadcastMessage(Utils::centerAlignText(TextFormat::BOLD . TextFormat::GOLD . "Item: " . TextFormat::RESET . $this->getItemName(), 50));
        Server::getInstance()->broadcastMessage(Utils::centerAlignText(TextFormat::BOLD . TextFormat::GOLD . "Time: " . TextFormat::RESET . TextFormat::GRAY . "5m", 50));
        Server::getInstance()->broadcastMessage(Utils::centerAlignText(TextFormat::GRAY . "Use /bah to view and place bids.", 50));
        Server::getInstance()->broadcastMessage(" ");
    }

    public function announceAlert(): void {
        if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_SIX)) {
            return;
        }
        Server::getInstance()->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . "[/bah] " . $this->getItemName() . TextFormat::RESET . TextFormat::GRAY . " has 60 seconds remaining!");
    }

    public function placeBid(NexusPlayer $player, int $bid): void {
        if($bid <= $this->bid) {
            $player->sendMessage(Translation::RED . TextFormat::RED . "This bid offer has expired. The new offer is $" . number_format($this->getNextBidPrice()));
            return;
        }
        if($this->bidder !== null) {
            if($this->bidder === $player->getName()) {
                $player->sendMessage(Translation::RED . TextFormat::RED . "You current possess the highest bidding!");
                return;
            }
            /** @var NexusPlayer $onlineBidder */
            $onlineBidder = Server::getInstance()->getPlayerByPrefix($this->bidder);
            if($onlineBidder !== null) {
                if($onlineBidder->isLoaded()) {
                    $onlineBidder->getDataSession()->addToBalance($this->bid, false);
                }
                $onlineBidder->sendMessage(Translation::RED . TextFormat::RED . "You have been outbid by " . $player->getName() . " on " . $this->item->getCustomName());
            }
            else {
                $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE stats SET balance = balance + ? WHERE username = ?");
                $stmt->bind_param("is", $this->bid, $this->bidder);
                $stmt->execute();
                $stmt->close();
            }
        }
        $this->bid = $bid;
        $this->bidder = $player->getName();
        if($this->getTimeLeft() <= 15) {
            $this->length += 15;
            Server::getInstance()->broadcastMessage(TextFormat::RED . TextFormat::BOLD . "[/bah] " . TextFormat::RESET . TextFormat::RED . "Due to a bid last moment, the auction has been extended!");
        }
        Server::getInstance()->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . "[/bah] " . TextFormat::RESET . TextFormat::YELLOW . $player->getName() . TextFormat::GRAY . " has bid " . TextFormat::YELLOW . "$" . number_format($bid) . TextFormat::GRAY . " on " . $this->getItemName());
        $player->sendMessage(Translation::ORANGE . TextFormat::GOLD . "You have placed a bid $" . number_format($bid) . " on " . $this->item->getCustomName());
        $player->getDataSession()->subtractFromBalance($bid, false);
    }

    public function sell(): void {
        if($this->bidder !== null) {
            /** @var NexusPlayer $onlineBidder */
            $onlineBidder = Server::getInstance()->getPlayerByPrefix($this->bidder);
            if($onlineBidder === null) {
                $database = Nexus::getInstance()->getMySQLProvider()->getDatabase();
                $stmt = $database->prepare("SELECT inbox FROM players WHERE username = ?;");
                $stmt->bind_param("s", $this->bidder);
                $stmt->execute();
                $stmt->bind_result($inbox);
                $stmt->fetch();
                $stmt->close();
                $items = [];
                if($inbox !== null) {
                    $items = Nexus::decodeInventory($inbox);
                }
                $items[] = $this->item;
                $inbox = Nexus::encodeItems($items);
                $stmt = $database->prepare("UPDATE players SET inbox = ? WHERE username = ?;");
                $stmt->bind_param("ss", $inbox, $this->bidder);
                $stmt->execute();
                $stmt->close();
            } elseif($onlineBidder->isLoaded()) {
                $onlineBidder->getDataSession()->addToInbox($this->item);
                Server::getInstance()->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . "[/bah] " . TextFormat::RESET . TextFormat::GOLD . "$this->bidder won the /bah on:");
                Server::getInstance()->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . "  Item: " . TextFormat::RESET . $this->getItemName());
                Server::getInstance()->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . "  Winning Bid: " . TextFormat::RESET . TextFormat::YELLOW . "$" . number_format($this->bid));
            }
        }
        else {
            $this->bidder = "None";
        }
        Nexus::getInstance()->getGameManager()->getBlackAuctionManager()->resetActiveAuction();
        Nexus::getInstance()->getGameManager()->getBlackAuctionManager()->addSellRecord($this->item, $this->bid, $this->bidder);
    }

    public function getTimeLeft(): int {
        return $this->length - (time() - $this->time);
    }

    /**
     * @return int
     */
    public function getNextBidPrice(): int {
        return $this->bid + $this->getBidIncrement();
    }

    /**
     * @return int
     */
    public function getBidIncrement(): int {
        if($this->bid < 100000) {
            return 10000;
        }
        if($this->bid < 1000000) {
            return 50000;
        }
        if($this->bid < 10000000) {
            return 500000;
        }
        if($this->bid < 20000000) {
            return 1000000;
        }
        if($this->bid < 50000000) {
            return 2000000;
        }
        if($this->bid < 100000000) {
            return 5000000;
        }
        if($this->bid < 250000000) {
            return 10000000;
        }
        if($this->bid < 500000000) {
            return 25000000;
        }
        return 100000000;
    }

    /**
     * @return int
     */
    public function getBid(): int {
        return $this->bid;
    }

    /**
     * @return string|null
     */
    public function getBidder(): ?string {
        return $this->bidder;
    }
}