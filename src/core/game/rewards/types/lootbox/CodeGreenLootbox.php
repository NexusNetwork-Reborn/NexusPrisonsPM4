<?php
declare(strict_types=1);

namespace core\game\rewards\types\lootbox;

use core\game\item\enchantment\EnchantmentIdentifiers;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\rewards\RewardsManager;
use core\game\rewards\types\LootboxRewards;
use core\game\rewards\types\MonthlyRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use core\game\rewards\Reward;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\utils\TextFormat;
use core\game\item\types\custom\EnchantmentPage;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\Title;
use core\game\item\types\custom\ChargeOrb;
use pocketmine\item\Item;

class CodeGreenLootbox extends LootboxRewards
{

    public function __construct()
    {
        $rewards = [
            new Reward("Legendary Page (10%)", function (?NexusPlayer $player): Item {
                $item = (new EnchantmentPage(4, 10, 90))->toItem()->setCount(10);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 55),
            new Reward("2,000,000 Energy", function (?NexusPlayer $player): Item {
                $item = (new Energy(2000000))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 30),
            new Reward("Mystery Legendary Enchant", function (?NexusPlayer $player): Item {
                $item = (new MysteryEnchantmentBook('Legendary'))->toItem()->setCount(3);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Godly Enchant", function (?NexusPlayer $player): Item {
                $item = (new MysteryEnchantmentBook('Godly'))->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 20),
            new Reward("Silence III (75%)", function (?NexusPlayer $player): Item {
                $args = [10, 3, 75, 25]; // ID, Level, Success Chance, Destroy Chance
                $enchantment = new EnchantmentInstance(EnchantmentManager::getEnchantment(EnchantmentIdentifiers::SILENCE), $args[1]);
                $item = (new EnchantmentBook($enchantment, $args[2], $args[3]))->toItem();
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 40),
            new Reward("Hero GKit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Hero', 1);
                $item = (new GKitFlare($kit, false))->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            new Reward("Mystery XP Booster", function (?NexusPlayer $player): Item {
                $item = (new XPBooster(3, 15))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Emerald Kopis Item Skin", function (?NexusPlayer $player): Item {
                $item = ItemManager::getSkinScroll("emerald_kopis")->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25),
            new Reward("Emerald Pickaxe Item Skin", function (?NexusPlayer $player): Item {
                $item = ItemManager::getSkinScroll("emerald_pickaxe")->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25)
        ];
        $jackpot = [
            new Reward("Lootbox: Crash Landing", function (?NexusPlayer $player): Item {
                $item = (new Lootbox(RewardsManager::CRASH_LANDING))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10),
            new Reward("Back To School 2022", function (?NexusPlayer $player): Item {
                $item = (new AethicCrate(MonthlyRewards::BACK_TO_SCHOOL, 2022))->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5)
        ];
        $bonus = [
            new Reward("Title Unlucky", function (?NexusPlayer $player): Item {
                $item = (new Title('Unlucky'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
            new Reward("+15% Charge Orb", function (?NexusPlayer $player): Item {
                $item = (new ChargeOrb(15))->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50)
        ];
        $coloredName = TextFormat::GREEN . TextFormat::BOLD . "Code Green";
        $lore = "Will you be Lucky or Unlucky?";
        $rewardCount = 4;
        $display = VanillaBlocks::EMERALD()->asItem();
        parent::__construct(TextFormat::clean("Code Green"), $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }

}