<?php
declare(strict_types=1);

namespace core\game\rewards\types\lootbox;

use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\types\custom\MysteryTrinketBox;
use core\game\rewards\types\LootboxRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use core\game\rewards\Reward;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\MeteorFlare;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\Title;

class InterstellarLootbox extends LootboxRewards
{

    public function __construct()
    {
        $rewards = [
            new Reward("3,500,000 Energy", function (?NexusPlayer $player): Item {
                $item = (new Energy(3500000))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 30),
            new Reward("3x Godly Contraband", function (?NexusPlayer $player): Item {
                $item = (new Contraband('Godly'))->toItem()->setCount(3);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 60),
            new Reward("Vulkarion G-Kit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Vulkarion', 1);
                $item = (new GKitFlare($kit, false))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            new Reward("2x Meteor Flare", function (?NexusPlayer $player): Item {
                $item = (new MeteorFlare())->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 80),
            new Reward("XP Booster", function (?NexusPlayer $player): Item {
                $item = (new XPBooster(3, 15))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
            /*new Reward("Warp Miner I (100%)", function (?NexusPlayer $player): Item {
                $args = [10, 3, 100, 0]; // ID, Level, Success Chance, Destroy Chance
                $enchantment = new EnchantmentInstance(EnchantmentManager::getEnchantment($args[0]), $args[1]);
                $item = (new EnchantmentBook($enchantment, $args[2], $args[3]))->toItem();
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 30),*/
            new Reward("Hero G-Kit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Hero', 1);
                $item = (new GKitFlare($kit, false))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            new Reward("Warlock G-Kit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Warlock', 1);
                $item = (new GKitFlare($kit, false))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            /*new Reward("Obsidian Sword Item Skin", function (?NexusPlayer $player): Item {
                $item = ItemManager::getSkinScroll("obsidian_sword")->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25),
            new Reward("Obsidian Pickaxe Item Skin", function (?NexusPlayer $player): Item {
                $item = ItemManager::getSkinScroll("obsidian_pickaxe")->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25)*/
        ];
        $jackpot = [
            new Reward("Lootbox: Solitary", function (?NexusPlayer $player): Item {
                $item = (new Lootbox('Solitary'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5),
            new Reward("Rank <V>", function (?NexusPlayer $player): Item {
                $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(5)))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 1),
            new Reward("Back To School 2017", function (?NexusPlayer $player): Item {
                $item = (new AethicCrate('Back To School', 2022))->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5),
            new Reward("Mystery Trinket Box", function (?NexusPlayer $player): Item {
                $item = (new MysteryTrinketBox())->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65)
        ];
        $bonus = [
            new Reward("Title Top Tier", function (?NexusPlayer $player): Item {
                $item = (new Title('Top Tier'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
            new Reward("Mystery Contraband", function (?NexusPlayer $player): Item {
                $args = ["Simple", "Uncommon", "Elite", "Ultimate", "Legendary", "Godly"];
                $item = (new Contraband($args[mt_rand(0, 5)]))->toItem()->setCount(3);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50)
        ];
        $coloredName = TextFormat::BOLD . TextFormat::RED . "Inter" . TextFormat::AQUA . "stellar";
        $lore = "It's out of this world!";
        $rewardCount = 4;
        $display = VanillaBlocks::EMERALD()->asItem();
        parent::__construct("Interstellar", $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }

}