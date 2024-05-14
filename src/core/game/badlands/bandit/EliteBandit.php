<?php

namespace core\game\badlands\bandit;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\Item;

class EliteBandit extends BaseBandit {

    const DEFAULT_DAMAGE = 2.5;

    public float $attackDamage = 2.5;
    public float $speed = 1;
    public float $range = 20;
    public string $networkId;
    public int $attackRate = 20;
    public int $attackDelay = 0;
    public int $knockbackTicks = 0;
    public int $attackRange = 2;
    public Item $heldItem;
    public int $tillDespawn = 12000;
    public float $modifier = 0.8;

    public function getName(): string
    {
        return "Elite Bandit";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2, 1);
    }
}
