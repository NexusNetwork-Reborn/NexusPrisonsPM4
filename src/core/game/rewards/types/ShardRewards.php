<?php
declare(strict_types=1);

namespace core\game\rewards\types;

use core\game\rewards\Reward;
use core\game\rewards\Rewards;

class ShardRewards extends Rewards {

    /** @var string */
    protected $rarity;

    /**
     * ShardRewards constructor.
     *
     * @param string $rarity
     * @param array $rewards
     */
    public function __construct(string $rarity, array $rewards) {
        $this->rarity = $rarity;
        parent::__construct($rewards);
    }

    /**
     * @return string
     */
    public function getRarity(): string {
        return $this->rarity;
    }

    /**
     * @param int $loop
     * @param int $maxChance
     *
     * @return Reward
     */
    public function getReward(int $loop = 0, int $maxChance = 10000): Reward {
        return parent::getReward($loop, $maxChance);
    }
}