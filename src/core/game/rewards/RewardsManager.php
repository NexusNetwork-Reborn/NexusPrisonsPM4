<?php
declare(strict_types=1);

namespace core\game\rewards;

use core\game\rewards\types\contraband\EliteContraband;
use core\game\rewards\types\contraband\GodlyContraband;
use core\game\rewards\types\contraband\LegendaryContraband;
use core\game\rewards\types\contraband\SimpleContraband;
use core\game\rewards\types\contraband\UltimateContraband;
use core\game\rewards\types\contraband\UncommonContraband;
use core\game\rewards\types\ContrabandRewards;
use core\game\rewards\types\lootbox\AbductedLootbox;
use core\game\rewards\types\lootbox\CodeGreenLootbox;
use core\game\rewards\types\lootbox\CorruptionLootbox;
use core\game\rewards\types\lootbox\CrashLandingLootbox;
use core\game\rewards\types\lootbox\InterstellarLootbox;
use core\game\rewards\types\lootbox\LockdownLootbox;
use core\game\rewards\types\lootbox\PrisonBreakLootbox;
use core\game\rewards\types\lootbox\SafetyFirstLootbox;
use core\game\rewards\types\lootbox\SolitaryLootbox;
use core\game\rewards\types\lootbox\TimeMachineLootbox;
use core\game\rewards\types\lootbox\UFOLootbox;
use core\game\rewards\types\lootbox\VoteLootbox;
use core\game\rewards\types\LootboxRewards;
use core\game\rewards\types\monthly\seventeen\AugustCrate;
use core\game\rewards\types\monthly\seventeen\BackToSchoolCrate;
use core\game\rewards\types\monthly\seventeen\ChristmasCrate;
use core\game\rewards\types\monthly\seventeen\NewYearCrate;
use core\game\rewards\types\MonthlyRewards;
use core\game\rewards\types\shard\EliteShard;
use core\game\rewards\types\shard\GodlyShard;
use core\game\rewards\types\shard\LegendaryShard;
use core\game\rewards\types\shard\SimpleShard;
use core\game\rewards\types\shard\UltimateShard;
use core\game\rewards\types\shard\UncommonShard;
use core\game\rewards\types\ShardRewards;
use core\Nexus;

class RewardsManager {

    const CURRENT_LOOTBOX = self::PRISON_BREAK;

    const VOTE = VoteLootbox::NAME;
    const CRASH_LANDING = CrashLandingLootbox::NAME;
    const PRISON_BREAK = PrisonBreakLootbox::NAME;
    const SAFETY_FIRST = SafetyFirstLootbox::NAME;
    const CODE_GREEN = "Code Green";

    /** @var Nexus */
    private $core;

    /** @var ContrabandRewards[] */
    private $contrabands = [];

    /** @var ShardRewards[] */
    private $shards = [];

    /** @var LootboxRewards[] */
    private $lootboxes = [];

    /** @var MonthlyRewards[][] */
    private $monthlys = [];

    /**
     * RewardsManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
    }

    public function init(): void {
        $this->registerContraband(new SimpleContraband());
        $this->registerContraband(new UncommonContraband());
        $this->registerContraband(new EliteContraband());
        $this->registerContraband(new UltimateContraband());
        $this->registerContraband(new LegendaryContraband());
        $this->registerContraband(new GodlyContraband());
        $this->registerShard(new SimpleShard());
        $this->registerShard(new UncommonShard());
        $this->registerShard(new EliteShard());
        $this->registerShard(new UltimateShard());
        $this->registerShard(new LegendaryShard());
        $this->registerShard(new GodlyShard());
        $this->registerLootbox(new VoteLootbox());
        $this->registerLootbox(new CrashLandingLootbox());
        $this->registerLootbox(new PrisonBreakLootbox());
        $this->registerLootbox(new SafetyFirstLootbox());
        $this->registerMonthly(new BackToSchoolCrate());
        $this->registerMonthly(new NewYearCrate());

        $this->registerMonthly(new AugustCrate());

        $this->registerLootbox(new CodeGreenLootbox());
        $this->registerLootbox(new TimeMachineLootbox());
        $this->registerLootbox(new UFOLootbox());
// ------------------------------------------------------------
        $this->registerLootbox(new AbductedLootbox());
        $this->registerLootbox(new InterstellarLootbox());
        $this->registerLootbox(new CorruptionLootbox());
        $this->registerLootbox(new LockdownLootbox());
        $this->registerLootbox(new SolitaryLootbox());

        $this->registerMonthly(new ChristmasCrate());
    }

    /**
     * @param ContrabandRewards $rewards
     */
    public function registerContraband(ContrabandRewards $rewards): void {
        if(isset($this->contrabands[$rewards->getRarity()])) {
            throw new RewardsException("Attempted to override a contraband with the rarity of \"{$rewards->getRarity()}\".");
        }
        $this->contrabands[$rewards->getRarity()] = $rewards;
    }

    /**
     * @param string $rarity
     *
     * @return ContrabandRewards
     */
    public function getContraband(string $rarity): ContrabandRewards {
        return $this->contrabands[$rarity];
    }

    /**
     * @param ShardRewards $rewards
     *
     * @throws RewardsException
     */
    public function registerShard(ShardRewards $rewards): void {
        if(isset($this->shards[$rewards->getRarity()])) {
            throw new RewardsException("Attempted to override a shard with the rarity of \"{$rewards->getRarity()}\".");
        }
        $this->shards[$rewards->getRarity()] = $rewards;
    }

    /**
     * @param string $rarity
     *
     * @return ShardRewards
     */
    public function getShard(string $rarity): ShardRewards {
        return $this->shards[$rarity];
    }

    /**
     * @param LootboxRewards $rewards
     *
     * @throws RewardsException
     */
    public function registerLootbox(LootboxRewards $rewards): void {
        if(isset($this->lootboxes[$rewards->getName()])) {
            throw new RewardsException("Attempted to override a lootbox with the name of \"{$rewards->getName()}\".");
        }
        $this->lootboxes[$rewards->getName()] = $rewards;
    }

    /**
     * @param string $name
     *
     * @return LootboxRewards
     */
    public function getLootbox(string $name): LootboxRewards {
        return $this->lootboxes[$name];
    }

    /**
     * @param MonthlyRewards $rewards
     *
     * @throws RewardsException
     */
    public function registerMonthly(MonthlyRewards $rewards): void {
        if(isset($this->monthlys[$rewards->getYear()][$rewards->getMonth()])) {
            throw new RewardsException("Attempted to override a monthly with the year of \"{$rewards->getYear()}\" and the month of \"{$rewards->getMonth()}\".");
        }
        $this->monthlys[$rewards->getYear()][$rewards->getMonth()] = $rewards;
    }

    /**
     * @param int $year
     * @param string $month
     *
     * @return MonthlyRewards
     */
    public function getMonthly(int $year, string $month): MonthlyRewards {
        return $this->monthlys[$year][$month] ?? $this->monthlys[2022][MonthlyRewards::BACK_TO_SCHOOL];
    }
}