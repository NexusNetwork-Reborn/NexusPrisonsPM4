<?php

declare(strict_types=1);

namespace core\game\item\cluescroll\types;

use core\game\combat\merchants\event\KillMerchantEvent;
use core\game\gamble\event\CoinFlipLoseEvent;
use core\game\item\cluescroll\Challenge;
use core\game\item\event\FailEnchantmentEvent;
use core\game\item\types\custom\ClueScroll;
use core\game\item\types\Rarity;
use core\player\NexusPlayer;
use pocketmine\block\BlockLegacyIds;
use pocketmine\item\Item;

class KillMerchantChallenge extends Challenge {

    const RARITY_TO_ORE = [
        Rarity::SIMPLE => BlockLegacyIds::IRON_ORE,
        Rarity::UNCOMMON => BlockLegacyIds::LAPIS_ORE,
        Rarity::ELITE => BlockLegacyIds::REDSTONE_ORE,
        Rarity::ULTIMATE => BlockLegacyIds::GOLD_ORE,
        Rarity::LEGENDARY => BlockLegacyIds::DIAMOND_ORE,
        Rarity::GODLY => BlockLegacyIds::EMERALD_ORE
    ];

    const ORE_TO_NAME = [
        BlockLegacyIds::IRON_ORE => "Iron",
        BlockLegacyIds::LAPIS_ORE => "Lapis",
        BlockLegacyIds::REDSTONE_ORE => "Redstone",
        BlockLegacyIds::GOLD_ORE => "Gold",
        BlockLegacyIds::DIAMOND_ORE => "Diamond",
        BlockLegacyIds::EMERALD_ORE => "Emerald"
    ];

    /**
     * KillMerchantChallenge constructor.
     *
     * @param int $id
     * @param string $rarity
     */
    public function __construct(int $id, string $rarity) {
        $callable = function(KillMerchantEvent $event, Item $scroll) {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $scrollInstance = ClueScroll::fromItem($scroll);
            if($scrollInstance === null) {
                return;
            }
            $challenge = $scrollInstance->getCurrentChallenge();
            if($challenge === $this->getId()) {
                $this->celebrate($player, $scroll, $scrollInstance);
            }
        };
        $description = "Defeat a(n) " . self::ORE_TO_NAME[self::RARITY_TO_ORE[$rarity]] . " Ore Merchant";
        parent::__construct($id, $description, $rarity, self::KILL_MERCHANT, $callable);
    }
}