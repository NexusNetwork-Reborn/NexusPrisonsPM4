<?php

namespace core\game\fund;

use core\game\fund\task\UpdateFundProgressTask;
use core\Nexus;
use pocketmine\utils\Config;

class FundManager {

    const PHASE_ONE = "Meteors";
    const PHASE_TWO = "/kit";
    const PHASE_THREE = "Meteorites";
    const PHASE_FOUR = "Alien Invasion";
    const PHASE_FIVE = "/gkit";
    const PHASE_SIX = "Black Market Auction";
    const PHASE_SEVEN = "Executive Enchantments";
    const PHASE_EIGHT = "Energy Enchantments";

    const PHASES = [
        self::PHASE_ONE,
        self::PHASE_TWO,
        self::PHASE_THREE,
        self::PHASE_FOUR,
        self::PHASE_FIVE,
        self::PHASE_SIX,
        self::PHASE_SEVEN,
        self::PHASE_EIGHT
    ];

    const BALANCE_REQUIREMENTS = [
        self::PHASE_ONE => 100000000,
        self::PHASE_TWO => 300000000,
        self::PHASE_THREE => 1000000000,
        self::PHASE_FOUR => 2000000000,
        self::PHASE_FIVE => 3000000000,
        self::PHASE_SIX => 5000000000,
        self::PHASE_SEVEN => 9000000000,
        self::PHASE_EIGHT => 15000000000
    ];

    const LEVEL_REQUIREMENTS = [
        self::PHASE_ONE => 60,
        self::PHASE_TWO => 70,
        self::PHASE_THREE => 80,
        self::PHASE_FOUR => 90,
        self::PHASE_FIVE => 95,
        self::PHASE_SIX => 100,
        self::PHASE_SEVEN => 101,
        self::PHASE_EIGHT => 102
    ];

    /** @var Nexus */
    private $core;

    /** @var string[] */
    private $unlocked;

    /** @var float */
    private $globalBalance = 0.0;

    /** @var array */
    private $globalRanks = [];

    /**
     * FundManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $config = new Config($this->core->getDataFolder() . "fund.json", Config::JSON);
        $this->unlocked = $config->get("unlocked", []);
        $core->getScheduler()->scheduleRepeatingTask(new UpdateFundProgressTask($this), 6000);
    }

    public function saveUnlocked(): void {
        $config = new Config($this->core->getDataFolder() . "fund.json", Config::JSON);
        $config->set("unlocked", $this->unlocked);
        $config->save();
    }

    /**
     * @param float $globalBalance
     */
    public function setGlobalBalance(float $globalBalance): void {
        $this->globalBalance = $globalBalance;
    }

    /**
     * @return float
     */
    public function getGlobalBalance(): float {
        return $this->globalBalance;
    }

    /**
     * @param array $globalRanks
     */
    public function setGlobalRanks(array $globalRanks): void {
        $this->globalRanks = $globalRanks;
    }

    /**
     * @return array
     */
    public function getGlobalRanks(): array {
        return $this->globalRanks;
    }

    /**
     * @param string $phase
     *
     * @return float
     */
    public function getFundProgressBalance(string $phase): float {
        $require = self::BALANCE_REQUIREMENTS[$phase] ?? 0;
        if($this->globalBalance >= $require) {
            return 100.000;
        }
        return min(100.000, round(($this->globalBalance / $require) * 100, 3));
    }

    /**
     * @param string $phase
     *
     * @return float
     */
    public function getFundProgressRanks(string $phase): float {
        $require = self::LEVEL_REQUIREMENTS[$phase] ?? 0;
        $players = 0;
        foreach($this->globalRanks as $rank) {
            if($rank >= $require) {
                $players++;
            }
        }
        return min(100.000, round($players * 10, 3));
    }

    /**
     * @param string $phase
     */
    public function setUnlocked(string $phase): void {
        $this->unlocked[] = $phase;
        $this->unlocked = array_unique($this->unlocked);
    }

    /**
     * @param string $phase
     *
     * @return bool
     */
    public function isUnlocked(string $phase): bool{
        return in_array($phase, $this->unlocked);
    }
}