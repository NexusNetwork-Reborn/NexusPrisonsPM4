<?php
declare(strict_types=1);

namespace core\game\blackAuction;

use core\game\blackAuction\task\BlackAuctionHeartbeatTask;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\EnchantmentOrb;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\Mask;
use core\game\item\types\custom\MeteorFlare;
use core\game\item\types\custom\MultiMask;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\MysteryEnchantmentOrb;
use core\game\item\types\custom\MysteryTrinketBox;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\custom\VaultExpansion;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\customies\ItemSkinScroll;
use core\game\item\types\Rarity;
use core\Nexus;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class BlackAuctionManager {

    const INTERVAL = 1800;

    /** @var Nexus */
    private $core;

    /** @var int */
    private $lastSold = 0;

    /** @var BlackAuctionRecord[] */
    private $recentlySold = [];

    /** @var Item[] */
    private $itemPool = [];

    /** @var null|BlackAuctionEntry */
    private $activeAuction = null;

    /**
     * BlackAuctionManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
        $core->getScheduler()->scheduleRepeatingTask(new BlackAuctionHeartbeatTask($this), 20);
    }

    public function init(): void {
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT soldTime, buyer, item, buyPrice FROM blackAuctionHistory ORDER BY soldTime DESC LIMIT 108");
        $stmt->execute();
        $stmt->bind_result($soldTime, $buyer, $item, $buyPrice);
        while($stmt->fetch()) {
            $this->recentlySold[$soldTime] = new BlackAuctionRecord(Nexus::decodeItem($item), $soldTime, $buyPrice, $buyer);
        }
        $stmt->close();
        $this->recalculateSales();
    }

    public function initItemPool(): void {
        $first = \core\game\item\mask\Mask::ALL_MASKS[array_rand(\core\game\item\mask\Mask::ALL_MASKS)];
        $second = \core\game\item\mask\Mask::ALL_MASKS[array_rand(\core\game\item\mask\Mask::ALL_MASKS)];
        if($first === $second){
            self::initItemPool();
            return;
        }
        $this->itemPool = [
            (new MysteryEnchantmentOrb(Enchantment::GODLY))->toItem()->setCount(1),
            (new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::EFFICIENCY2), 6), 100))->toItem()->setCount(1),
            (new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::LUCKY), 4), 100))->toItem()->setCount(1),
            (new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::MOMENTUM), 6), 100))->toItem()->setCount(1),
            (new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::WARP_MINER), 1), 100))->toItem()->setCount(1),
            (new EnchantmentBook(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::FINAL_STAND), 1), 35, 50))->toItem()->setCount(1),
            (new EnchantmentBook(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::DEMONIC_FRENZY), 1), 35, 50))->toItem()->setCount(1),
            (new EnchantmentBook(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::ENDLESS_PUMMEL), 1), 35, 50))->toItem()->setCount(1),
            (new EnchantmentBook(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::DIVINE_LIGHTNING), 1), 35, 50))->toItem()->setCount(1),
            (new EnchantmentBook(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::SYSTEM_ELECTROCUTION), 1), 50, 50))->toItem()->setCount(1),
            (new EnchantmentBook(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::ANTI_VIRUS), 1), 25, 50))->toItem()->setCount(1),
            (new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::TIME_WARP), 1), 100))->toItem()->setCount(1),
            (new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::METICULOUS_EFFICIENCY), 1), 100))->toItem()->setCount(1),
            (new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::ENERGY_HOARDER), 1), 100))->toItem()->setCount(1),
            (new PrestigeToken(3))->toItem()->setCount(1),
            (new PrestigeToken(5))->toItem()->setCount(1),
            (new PrestigeToken(8))->toItem()->setCount(1),
            (new PrestigeToken(10))->toItem()->setCount(1),
            (new ChargeOrb(20))->toItem()->setCount(1),
            (new MysteryTrinketBox())->toItem()->setCount(1),
            (new GKitFlare(null, false))->toItem()->setCount(1),
            (new VaultExpansion())->toItem()->setCount(1),
            (new Mask($first))->toItem()->setCount(1),
            (new Mask($second))->toItem()->setCount(1),
            (new MultiMask([$first, $second]))->toItem()->setCount(1),
            ItemManager::getSkinScroll(ItemSkinScroll::UNCOMMON[array_rand(ItemSkinScroll::UNCOMMON)])->toItem()->setCount(1),
            ItemManager::getSkinScroll(ItemSkinScroll::ELITE[array_rand(ItemSkinScroll::ELITE)])->toItem()->setCount(1),
            (new MeteorFlare())->toItem()->setCount(1),
        ];
    }

    /**
     * @return BlackAuctionEntry|null
     */
    public function getActiveAuction(): ?BlackAuctionEntry {
        return $this->activeAuction;
    }

    /**
     * @return int
     */
    public function getTimeBeforeNext(): int {
        return self::INTERVAL - (time() - $this->lastSold);
    }

    public function startAuction(): void {
        $item = $this->selectItem();
        $entry = new BlackAuctionEntry($item);
        $entry->announce();
        $this->activeAuction = $entry;
    }

    public function resetActiveAuction(): void {
        $this->activeAuction = null;
    }

    /**
     * @param Item $item
     * @param int $buyPrice
     * @param string $buyer
     */
    public function addSellRecord(Item $item, int $buyPrice, string $buyer): void {
        $sellTime = time();
        $this->recentlySold[$sellTime] = new BlackAuctionRecord($item, $sellTime, $buyPrice, $buyer);
        $item = Nexus::encodeItem($item);
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("INSERT INTO blackAuctionHistory(soldTime, buyer, item, buyPrice) VALUES(?, ?, ?, ?)");
        $stmt->bind_param("issi", $sellTime, $buyer, $item, $buyPrice);
        $stmt->execute();
        $stmt->close();
        $this->recalculateSales();
    }

    public function recalculateSales(): void {
        $maxTime = 0;
        foreach($this->recentlySold as $sold) {
            if($sold->getSoldTime() > $maxTime) {
                $maxTime = $sold->getSoldTime();
            }
        }
        $this->lastSold = $maxTime;
        krsort($this->recentlySold);
        if(count($this->recentlySold) > 108) {
            $this->recentlySold = array_slice($this->recentlySold, 0, 108, true);
        }
    }

    /**
     * @param int $loop
     *
     * @return Item
     */
    public function selectItem(int $loop = 0): Item {
        $item = $this->itemPool[array_rand($this->itemPool)];
        $lastTen = array_slice($this->recentlySold, 0, 10, true);
        foreach($lastTen as $record) {
            if($record->getItem()->equals($item)) {
                return $this->selectItem(++$loop);
            }
        }
        if($loop >= 10) {
            return $item;
        }
        return $item;
    }

    /**
     * @return BlackAuctionRecord[]
     */
    public function getRecentlySold(): array {
        return $this->recentlySold;
    }
}