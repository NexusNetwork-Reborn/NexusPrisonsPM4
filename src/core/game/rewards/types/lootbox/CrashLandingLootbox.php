<?php
declare(strict_types=1);

namespace core\game\rewards\types\lootbox;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\ChargeOrbSlot;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Cosmetic;
use core\game\item\types\custom\EnchantmentReroll;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\GKitBeacon;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\HomeExpansion;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\RandomGKit;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\ShowcaseExpansion;
use core\game\item\types\custom\Title;
use core\game\item\types\custom\VaultExpansion;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Pickaxe;
use core\game\kit\GodKit;
use core\game\rewards\Reward;
use core\game\rewards\types\ContrabandRewards;
use core\game\rewards\types\LootboxRewards;
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

class CrashLandingLootbox extends LootboxRewards {

    const NAME = "Crash Landing";

    /**
     * CrashLandingLootbox constructor.
     */
    public function __construct() {
        $rewards = [
            new Reward("Mystery Godly Enchant", function(?NexusPlayer $player): Item {
                $item = (new MysteryEnchantmentBook(Rarity::GODLY))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Hero G-Kit Flare", function(?NexusPlayer $player): Item {
                /** @var GodKit $kit */
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName("Hero");
                $item = (new GKitFlare($kit, false))->toItem()->setCount(1);
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
            new Reward("1,500,000 Energy", function(?NexusPlayer $player): Item {
                $item = (new Energy(1500000))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Charge Orb Slot", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrbSlot())->toItem()->setCount(10);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Charge Orb", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrb(10))->toItem()->setCount(6);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("WhiteScroll", function(?NexusPlayer $player): Item {
                $item = (new WhiteScroll())->toItem()->setCount(3);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("XP Booster", function(?NexusPlayer $player): Item {
                $item = (new XPBooster(2.5, 30))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Energy Booster", function(?NexusPlayer $player): Item {
                $item = (new EnergyBooster(2.5, 30))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
             new Reward("Tier II Rank Note", function(?NexusPlayer $player): Item {
                 $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::IMPERIAL)))->toItem()->setCount(1);
                 if($player !== null) {
                     $player->getInventory()->addItem($item);
                 }
                 return $item;
             }, 85)
        ];
        $jackpot = [
            new Reward("Random G-Kit Beacon", function(?NexusPlayer $player): Item {
                $item = (new RandomGKit())->toItem()->setCount(2);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
            new Reward("Tier V Rank Note", function(?NexusPlayer $player): Item {
                $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::EMPEROR)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10)
        ];
        $bonus = [
            new Reward("Ez Gains!", function(?NexusPlayer $player): Item {
                $display = VanillaBlocks::BEACON()->asItem();
                $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Ez Gains!";
                $lore = [];
                $lore[] = "";
                $lore[] = Nexus::SERVER_NAME . TextFormat::RESET . TextFormat::GRAY . " first ever " . TextFormat::WHITE . TextFormat::BOLD . "Lootbox!";
                $item = (new Cosmetic($display, $name, $lore, true))->toItem();
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Title FuRsT", function(?NexusPlayer $player): Item {
                $item = (new Title("FuRsT"))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100)
        ];
        $coloredName = TextFormat::RED . TextFormat::BOLD . "Crash Landing";
        $lore = "Only the best for success";
        $rewardCount = 4;
        $display = VanillaBlocks::EMERALD()->asItem();
        parent::__construct(self::NAME, $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }
}