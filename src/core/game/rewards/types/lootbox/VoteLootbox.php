<?php
declare(strict_types=1);

namespace core\game\rewards\types\lootbox;

use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\ChargeOrbSlot;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Cosmetic;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\MeteorFlare;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\RandomGKit;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\Title;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\Rarity;
use core\game\kit\GodKit;
use core\game\rewards\Reward;
use core\game\rewards\types\LootboxRewards;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class VoteLootbox extends LootboxRewards {

    const NAME = "Vote";

    /**
     * CrashLandingLootbox constructor.
     */
    public function __construct() {
        $rewards = [
            new Reward("Mystery Legendary Enchant", function(?NexusPlayer $player): Item {
                $item = (new MysteryEnchantmentBook(Rarity::LEGENDARY))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Charge Orb Slot", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrbSlot())->toItem()->setCount(2);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Charge Orb", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrb(10))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("WhiteScroll", function(?NexusPlayer $player): Item {
                $item = (new WhiteScroll())->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Blackscroll", function(?NexusPlayer $player): Item {
                $item = (new BlackScroll(50))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100)
        ];
        $jackpot = [
            new Reward("Godly Contraband", function(?NexusPlayer $player): Item {
                $item = (new Contraband(Rarity::GODLY))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25),
            new Reward("XP Booster", function(?NexusPlayer $player): Item {
                $item = (new XPBooster(2, 15))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
            new Reward("Meteor Flare", function(?NexusPlayer $player): Item {
                $item = (new MeteorFlare())->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
            new Reward("Tier II Rank Note", function(?NexusPlayer $player): Item {
                $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::IMPERIAL)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 1)
        ];
        $bonus = [
            new Reward("$100K", function(?NexusPlayer $player): Item {
                $item = (new MoneyNote(100000))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("100,000 Energy", function(?NexusPlayer $player): Item {
                $item = (new Energy(100000))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100)
        ];
        $coloredName = TextFormat::GREEN . TextFormat::BOLD . "Vote";
        $lore = "Obtained from voting at bit.ly/3m3AOdp";
        $rewardCount = 1;
        $display = VanillaBlocks::IRON()->asItem();
        parent::__construct(self::NAME, $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }
}