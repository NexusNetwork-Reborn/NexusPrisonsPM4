<?php
declare(strict_types=1);

namespace core\game\rewards\types\lootbox;

use core\game\item\ItemManager;
use core\game\rewards\types\LootboxRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use core\game\rewards\Reward;
use pocketmine\utils\TextFormat;
use pocketmine\item\ItemFactory;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Token;
use core\game\item\types\custom\MeteorFlare;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\VaultExpansion;
use core\game\item\types\custom\Trinket;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\Title;
use core\game\item\types\custom\Absorber;
use pocketmine\item\Item;

class UFOLootbox extends LootboxRewards
{

    public function __construct()
    {
        $rewards = [
            new Reward("3,750,000 Energy", function (?NexusPlayer $player): Item {
                $item = (new Energy(3750000))->toItem()->setCount(1);
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
            new Reward("16x Quest Token", function (?NexusPlayer $player): Item {
                $item = (new Token())->toItem()->setCount(16);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
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
            new Reward("Rank <IV>", function (?NexusPlayer $player): Item {
                $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(4)))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10),
            new Reward("Ares G-Kit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Ares');
                $item = (new GKitFlare($kit, false))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            new Reward("+2 PV Rows", function (?NexusPlayer $player): Item {
                $item = (new VaultExpansion())->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 85),
            new Reward("Quartz Macesword Item Skin", function (?NexusPlayer $player): Item {
                $item = ItemManager::getSkinScroll("quartz_macesword")->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 20),
            new Reward("Bonesteel Pickaxe Item Skin", function (?NexusPlayer $player): Item {
                $item = ItemManager::getSkinScroll("bonesteel_pickaxe")->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 20)
        ];
        $jackpot = [
            new Reward("Grappling Hook Trinket", function (?NexusPlayer $player): Item {
                $item = (new Trinket(\core\game\item\trinket\Trinket::GRAPPLING_TRINKET))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 45),
            new Reward("Resistance Trinket", function (?NexusPlayer $player): Item {
                $item = (new Trinket(\core\game\item\trinket\Trinket::RESISTANCE_TRINKET))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 45),
            new Reward("Back To School 2022", function (?NexusPlayer $player): Item {
                $item = (new AethicCrate('Back To School', 2022))->toItem()->setCount(2);
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
            }, 1)
        ];
        $bonus = [
            new Reward("Title #StayStrong", function (?NexusPlayer $player): Item {
                $item = (new Title('#StayStrong'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
            new Reward("12x Absorber", function (?NexusPlayer $player): Item {
                $item = (new Absorber())->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50)
        ];
        $coloredName = TextFormat::GREEN . "U" . TextFormat::RED . "F" . TextFormat::AQUA . "O";
        $lore = "The loot is out of this world!";
        $rewardCount = 4;
        $display = VanillaBlocks::EMERALD()->asItem();
        parent::__construct(TextFormat::clean("UFO"), $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }

}