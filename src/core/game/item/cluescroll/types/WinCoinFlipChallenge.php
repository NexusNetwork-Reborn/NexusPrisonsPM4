<?php

declare(strict_types=1);

namespace core\game\item\cluescroll\types;

use core\game\gamble\event\CoinFlipWinEvent;
use core\game\item\cluescroll\Challenge;
use core\game\item\event\TinkerEquipmentEvent;
use core\game\item\types\custom\ClueScroll;
use core\game\item\types\custom\Satchel;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use pocketmine\item\Item;

class WinCoinFlipChallenge extends Challenge {

    const RARITY_TO_AMOUNT = [
        Rarity::SIMPLE => 50000,
        Rarity::UNCOMMON => 100000,
        Rarity::ELITE => 150000,
        Rarity::ULTIMATE => 250000,
        Rarity::LEGENDARY => 500000,
        Rarity::GODLY => 1000000
    ];

    /**
     * TinkerEquipmentChallenge constructor.
     *
     * @param int $id
     * @param string $rarity
     */
    public function __construct(int $id, string $rarity) {
        $callable = function(CoinFlipWinEvent $event, Item $scroll) {
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
                $amount = $event->getAmount();
                $requiredAmount = self::RARITY_TO_AMOUNT[$this->rarity];
                if($amount >= $requiredAmount) {
                    $this->celebrate($player, $scroll, $scrollInstance);
                }
            }
        };
        $amount = self::RARITY_TO_AMOUNT[$rarity];
        $description = "Win $" . number_format($amount) . "+ from /coinflip";
        parent::__construct($id, $description, $rarity, self::COINFLIP_WIN, $callable);
    }
}