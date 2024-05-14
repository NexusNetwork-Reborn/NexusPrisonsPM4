<?php
declare(strict_types=1);

namespace core\game\rewards\types\lootbox;

use core\game\item\ItemManager;
use core\game\rewards\types\LootboxRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use core\game\rewards\Reward;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use core\game\item\types\custom\Absorber;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\Trinket;
use core\game\item\types\custom\Title;


class CorruptionLootbox extends LootboxRewards
{

    public function __construct()
    {
        $rewards = [
            new Reward("16x Absorber", function (?NexusPlayer $player): Item {
                $item = (new Absorber())->toItem()->setCount(16);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("2,000,000 Energy", function (?NexusPlayer $player): Item {
                $item = (new Energy(2000000))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 30),
            new Reward("2x Legendary Contraband", function (?NexusPlayer $player): Item {
                $item = (new Contraband('Legendary'))->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("XP Booster", function (?NexusPlayer $player): Item {
                $item = (new XPBooster(3, 15))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
            new Reward("Energy Booster", function (?NexusPlayer $player): Item {
                $item = (new EnergyBooster(3, 15))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
            new Reward("Hero GKit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Hero', 1);
                $item = (new GKitFlare($kit, false))->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            new Reward("Rank <II>", function (?NexusPlayer $player): Item {
                $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(2)))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10),
            new Reward("Dark Spatha Item Skin", function (?NexusPlayer $player): Item {
                $item = ItemManager::getSkinScroll("dark_spatha")->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25),
            new Reward("Bone Pickaxe Item Skin", function (?NexusPlayer $player): Item {
                $item = ItemManager::getSkinScroll("bone_pickaxe")->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25)
        ];
        $jackpot = [
            new Reward("Rank <V>", function (?NexusPlayer $player): Item {
                $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(5)))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5),
            new Reward("Back To School 2017", function (?NexusPlayer $player): Item {
                $item = (new AethicCrate('Back To School', 2022))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25),
            new Reward("Healing Trinket", function (?NexusPlayer $player): Item {
                $item = (new Trinket('Healing' . " Trinket"))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 45)
        ];
        $bonus = [
            new Reward("Title #Hooked", function (?NexusPlayer $player): Item {
                $item = (new Title('#Hooked'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
            new Reward("Vulkarion GKit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Vulkarion', 1);
                $item = (new GKitFlare($kit, false))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50)
        ];
        $coloredName = TextFormat::DARK_PURPLE . TextFormat::BOLD . "Corruption";
        $lore = "What the guards don't know what hurt them!?";
        $rewardCount = 4;
        $display = VanillaBlocks::EMERALD()->asItem();
        parent::__construct("Corruption", $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }

}