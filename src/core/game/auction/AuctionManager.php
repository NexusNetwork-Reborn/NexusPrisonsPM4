<?php

namespace core\game\auction;

use core\Nexus;
use pocketmine\item\Durable;
use pocketmine\player\Player;

class AuctionManager {

    /** @var Nexus */
    private $core;

    /** @var int */
    private $id;

    /** @var AuctionEntry[]  */
    private $auctionEntries = [];

    /**
     * AuctionManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $stmt = $core->getMySQLProvider()->getDatabase()->prepare("SELECT identifier, seller, item, startTime, buyPrice FROM auctions");
        $stmt->execute();
        $stmt->bind_result($identifier, $seller, $item, $startTime, $buyPrice);
        while($stmt->fetch()) {
            $this->auctionEntries[$identifier] = new AuctionEntry(Nexus::decodeItem($item), $seller, $identifier, $startTime, $buyPrice);
        }
        $stmt->close();
        $stmt = $core->getMySQLProvider()->getDatabase()->prepare("SELECT MAX(identifier) FROM auctions");
        $stmt->execute();
        $stmt->bind_result($identifier);
        $this->id = $identifier;
    }

    /**
     * @return int
     */
    public function getNewIdentifier(): int {
        return ++$this->id;
    }

    /**
     * @param int $page
     *
     * @return AuctionEntry[][]
     */
    public function getPage(int $page = 1): array {
        $sorted = [];
        foreach($this->auctionEntries as $entry) {
            if(!$entry->isRunning()) {
                continue;
            }
            $item = $entry->getItem();
            if($item instanceof Durable) {
                $sorted[$item->getId() . ":0"][] = $entry;
                continue;
            }
            $sorted[$item->getId() . ":" . $item->getMeta()][] = $entry;
        }
        return array_chunk($sorted, 36, true)[$page - 1] ?? [];
    }

    /**
     * @return int
     */
    public function getMaxPages(): int {
        $sorted = [];
        foreach($this->auctionEntries as $entry) {
            if(!$entry->isRunning()) {
                continue;
            }
            $item = $entry->getItem();
            if($item instanceof Durable) {
                $sorted[$item->getId() . ":0"][] = $entry;
                continue;
            }
            $sorted[$item->getId() . ":" . $item->getMeta()][] = $entry;
        }
        return ceil(count($sorted) / 36);
    }

    /**
     * @return AuctionEntry[]
     */
    public function getEntries(): array {
        return $this->auctionEntries;
    }

    /**
     * @param int $identifier
     *
     * @return AuctionEntry|null
     */
    public function getEntry(int $identifier): ?AuctionEntry {
        if($this->entryExists($identifier) === true) {
            return $this->auctionEntries[$identifier];
        }
        return null;
    }

    /**
     * @param Player $player
     *
     * @return AuctionEntry[]
     */
    public function getEntriesOf(Player $player): array {
        $entries = [];
        foreach($this->auctionEntries as $entry) {
            if($entry->getSeller() === $player->getName()) {
                $entries[] = $entry;
            }
        }
        return $entries;
    }

    /**
     * @param AuctionEntry $entry
     */
    public function addEntry(AuctionEntry $entry) {
        $this->auctionEntries[$entry->getIdentifier()] = $entry;
        $identifier = $entry->getIdentifier();
        $seller = $entry->getSeller();
        $buyPrice = $entry->getBuyPrice();
        $item = Nexus::encodeItem($entry->getItem());
        $startTime = $entry->getStartTime();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("INSERT INTO auctions(identifier, seller, item, startTime, buyPrice) VALUES(?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $identifier, $seller, $item, $startTime, $buyPrice);
        $stmt->execute();
    }

    /**
     * @param int $identifier
     */
    public function removeEntry(int $identifier): void {
        unset($this->auctionEntries[$identifier]);
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("DELETE FROM auctions WHERE identifier = ?");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
    }

    /**
     * @param int $identifier
     *
     * @return bool
     */
    public function entryExists(int $identifier): bool {
        return isset($this->auctionEntries[$identifier]);
    }

    /**
     * @param AuctionEntry $entry
     */
    public function updateEntry(AuctionEntry $entry) {
        $item = Nexus::encodeItem($entry->getItem());;
        $identifier = $entry->getIdentifier();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE auctions SET item = ? WHERE identifier = ?");
        $stmt->bind_param("si", $item, $identifier);
        $stmt->execute();
    }
}