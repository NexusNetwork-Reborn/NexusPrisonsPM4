<?php

namespace core\game;

use core\game\auction\AuctionManager;
use core\game\badlands\BadlandsManager;
use core\game\blackAuction\BlackAuctionManager;
use core\game\boop\BOOPManager;
use core\game\combat\CombatManager;
use core\game\economy\EconomyManager;
use core\game\fund\FundManager;
use core\game\gamble\GambleManager;
use core\game\item\ItemManager;
use core\game\kit\KitManager;
use core\game\plots\PlotManager;
use core\game\quest\QuestManager;
use core\game\rewards\RewardsManager;
use core\game\trade\TradeManager;
use core\game\wormhole\WormholeManager;
use core\game\zone\ZoneManager;
use core\Nexus;

class GameManager {

    /** @var Nexus */
    private $core;

    /** @var AuctionManager */
    private $auctionManager;

    /** @var BOOPManager */
    private $boopManager;

    /** @var CombatManager */
    private $combatManager;

    /** @var EconomyManager */
    private $economyManager;

    /** @var GambleManager */
    private $gambleManager;

    /** @var ItemManager */
    private $itemManager;

    /** @var RewardsManager */
    private $rewardsManager;

    /** @var KitManager */
    private $kitManager;

    /** @var TradeManager */
    private $tradeManager;

    /** @var WormholeManager */
    private $wormholeManager;

    /** @var ZoneManager */
    private $zoneManager;

    /** @var QuestManager */
    private $questManager;

    /** @var PlotManager */
    private $plotManager;

    /** @var FundManager */
    private $fundManager;

    /** @var BlackAuctionManager */
    private $blackAuctionManager;

    /** @var BadlandsManager */
    private $badlandsManager;

    /**
     * GameManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
    }

    public function init(): void {
        $this->itemManager = new ItemManager($this->core);
        $this->auctionManager = new AuctionManager($this->core);
        $this->boopManager = new BOOPManager($this->core);
        $this->combatManager = new CombatManager($this->core);
        $this->economyManager = new EconomyManager($this->core);
        $this->gambleManager = new GambleManager($this->core);
        $this->rewardsManager = new RewardsManager($this->core);
        $this->kitManager = new KitManager($this->core);
        $this->tradeManager = new TradeManager($this->core);
        $this->wormholeManager = new WormholeManager($this->core);
        $this->zoneManager = new ZoneManager($this->core);
        $this->questManager = new QuestManager($this->core);
        $this->plotManager = new PlotManager($this->core);
        $this->fundManager = new FundManager($this->core);
        $this->blackAuctionManager = new BlackAuctionManager($this->core);
       # $this->badlandsManager = new BadlandsManager();
    }

    /**
     * @return AuctionManager
     */
    public function getAuctionManager(): AuctionManager {
        return $this->auctionManager;
    }

    /**
     * @return BOOPManager
     */
    public function getBOOPManager(): BOOPManager {
        return $this->boopManager;
    }

    /**
     * @return CombatManager
     */
    public function getCombatManager(): CombatManager {
        return $this->combatManager;
    }

    /**
     * @return EconomyManager
     */
    public function getEconomyManager(): EconomyManager {
        return $this->economyManager;
    }

    /**
     * @return GambleManager
     */
    public function getGambleManager(): GambleManager {
        return $this->gambleManager;
    }

    /**
     * @return ItemManager
     */
    public function getItemManager(): ItemManager {
        return $this->itemManager;
    }

    /**
     * @return RewardsManager
     */
    public function getRewardsManager(): RewardsManager {
        return $this->rewardsManager;
    }

    /**
     * @return KitManager
     */
    public function getKitManager(): KitManager {
        return $this->kitManager;
    }

    /**
     * @return TradeManager
     */
    public function getTradeManager(): TradeManager {
        return $this->tradeManager;
    }

    /**
     * @return WormholeManager
     */
    public function getWormholeManager(): WormholeManager {
        return $this->wormholeManager;
    }

    /**
     * @return ZoneManager
     */
    public function getZoneManager(): ZoneManager {
        return $this->zoneManager;
    }

    /**
     * @return QuestManager
     */
    public function getQuestManager(): QuestManager {
        return $this->questManager;
    }

    /**
     * @return PlotManager
     */
    public function getPlotManager(): PlotManager {
        return $this->plotManager;
    }

    /**
     * @return FundManager
     */
    public function getFundManager(): FundManager {
        return $this->fundManager;
    }

    /**
     * @return BlackAuctionManager
     */
    public function getBlackAuctionManager(): BlackAuctionManager {
        return $this->blackAuctionManager;
    }

    /**
     * @return BadlandsManager
     */
    public function getBadlandsManager() : BadlandsManager
    {
        return $this->badlandsManager;
    }
}