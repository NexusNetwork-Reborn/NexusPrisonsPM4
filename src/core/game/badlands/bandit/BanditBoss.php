<?php

namespace core\game\badlands\bandit;

use core\game\zone\BadlandsRegion;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\Item;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\item\enchantment\EnchantmentInstance;
use core\game\item\types\vanilla\Armor;

class BanditBoss extends BaseBandit {

    const DEFAULT_DAMAGE = 1.5;

    public null|BadlandsRegion $region = null;
    public float $attackDamage = 5;
    public float $speed = 0.6;
    public float $range = 1;
    public string $networkId;
    public int $attackRate = 30;
    public int $attackDelay = 0;
    public int $knockbackTicks = 0;
    public int $attackRange = 2;
    public Item $heldItem;
    public int $tillDespawn = 12000;

    public float $modifier = 0.7;

    public function __construct(Location $location, CompoundTag $nbt)
    {
        parent::__construct($location, $nbt);
        $this->heldItem = match ($this->region->getTier()) {
            Armor::TIER_CHAIN => VanillaItems::STONE_AXE(),
            Armor::TIER_GOLD => VanillaItems::GOLDEN_AXE(),
            Armor::TIER_IRON => VanillaItems::IRON_AXE(),
            default => VanillaItems::DIAMOND_AXE(),
        };
        $this->heldItem->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
    }

    public function getName(): string
    {
        return "Bandit Boss";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2, 1);
    }
}
