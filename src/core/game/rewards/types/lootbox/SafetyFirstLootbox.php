<?php
declare(strict_types=1);

namespace core\game\rewards\types\lootbox;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\mask\Mask;
use core\game\item\types\custom\Absorber;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\ChargeOrbSlot;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Cosmetic;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\EnchantmentOrb;
use core\game\item\types\custom\EnchantmentPage;
use core\game\item\types\custom\EnchantmentReroll;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\GKitBeacon;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\HomeExpansion;
use core\game\item\types\custom\ItemNameTag;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\MeteorFlare;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\custom\MultiMask;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\MysteryEnchantmentOrb;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\custom\RandomGKit;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\ShowcaseExpansion;
use core\game\item\types\custom\Title;
use core\game\item\types\custom\Token;
use core\game\item\types\custom\Trinket;
use core\game\item\types\custom\VaultExpansion;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Pickaxe;
use core\game\kit\GodKit;
use core\game\rewards\Reward;
use core\game\rewards\types\ContrabandRewards;
use core\game\rewards\types\LootboxRewards;
use core\game\rewards\types\MonthlyRewards;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class SafetyFirstLootbox extends LootboxRewards {

    const NAME = "Safety First";

    /**
     * CrashLandingLootbox constructor.
     */
    public function __construct() {
        $rewards = [
            new Reward("Absorber", function(?NexusPlayer $player): Item {
                $item = (new Absorber())->toItem()->setCount(16);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Item Name Tag", function(?NexusPlayer $player): Item {
                $item = (new ItemNameTag())->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Godly Contraband", function(?NexusPlayer $player): Item {
                $item = (new Contraband(Rarity::GODLY))->toItem()->setCount(2);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Charge Orb", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrb(20))->toItem()->setCount(3);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Meteor Flare", function(?NexusPlayer $player): Item {
                $item = (new MeteorFlare())->toItem()->setCount(2);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Vault Expansion", function(?NexusPlayer $player): Item {
                $item = (new VaultExpansion())->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Home Expansion", function(?NexusPlayer $player): Item {
                $item = (new HomeExpansion())->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Random G-Kit", function(?NexusPlayer $player): Item {
                $item = (new RandomGKit())->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Holiday", function(?NexusPlayer $player): Item {
                $item = (new AethicCrate(MonthlyRewards::HOLIDAY, 2022))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Absorption Trinket", function(?NexusPlayer $player): Item {
                $item = (new Trinket(\core\game\item\trinket\Trinket::ABSORPTION_TRINKET))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("XP Booster", function(?NexusPlayer $player): Item {
                $item = (new XPBooster(2.5, 30))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Ulterior Rank", function(?NexusPlayer $player): Item {
                $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::MAJESTY)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
        ];
        $jackpot = [
            new Reward("3x Godly Enchantment", function(?NexusPlayer $player): Item {
                $item = (new MysteryEnchantmentOrb(Enchantment::GODLY))->toItem()->setCount(3);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Heroic Crystal", function(?NexusPlayer $player): Item {
                $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::EMPEROR_HEROIC)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 15)
        ];
        $bonus = [
            new Reward("Title #Yeet", function(?NexusPlayer $player): Item {
                $item = (new Title("#Yeet"))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Godly Contraband", function(?NexusPlayer $player): Item {
                $item = (new Contraband(Rarity::GODLY))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
        ];
        $coloredName = TextFormat::DARK_RED . TextFormat::BOLD . "Safety First";
        $lore = "Always use protection!";
        $rewardCount = 5;
        $display = VanillaBlocks::EMERALD()->asItem();
        parent::__construct(self::NAME, $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }
}