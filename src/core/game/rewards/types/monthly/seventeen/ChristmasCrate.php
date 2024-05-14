<?php

declare(strict_types=1);

namespace core\game\rewards\types\monthly\seventeen;

use core\game\item\ItemManager;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Cosmetic;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\ItemNameTag;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\Shard;
use core\game\item\types\custom\SpaceVisor;
use core\game\item\types\custom\Title;
use core\game\item\types\custom\Token;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Fireworks;
use core\game\rewards\Reward;
use core\game\rewards\types\contraband\LegendaryContraband;
use core\game\rewards\types\MonthlyRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class ChristmasCrate extends MonthlyRewards {

    public function __construct() {
        //$coloredName = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Back 2 School 2017";
        $coloredName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "COSMIC CRATE: " . TextFormat::GREEN . "Christmas 2022";
        $adminItems = [
            new Reward("Happy Holidays", function(?NexusPlayer $player): Item {
                $display = VanillaBlocks::FERN()->asItem();
                $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Happy" . TextFormat::RED . " Holidays";
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Happy New Year" . TextFormat::RED . "<3";
                $item = (new Cosmetic($display, $name, $lore, true))->toItem();
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Holiday Tunes", function(?NexusPlayer $player): Item {
                $display = VanillaItems::RECORD_11();
                $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_AQUA . "Holiday Tunes";
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::ITALIC . TextFormat::RED . "The best holiday songs 2021";
                $item = (new Cosmetic($display, $name, $lore, true))->toItem();
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100)
        ];
        $cosmetics = [
            new Reward("Chilly Titles", function(NexusPlayer $player): Item {
                return (new Title(["Chilly", "Freezy", "Snowy", "#Winter"][mt_rand(0, 1)]))->toItem();
            }, 100),
            new Reward("PermaFrost Item Skin", function(NexusPlayer $player): Item {
                if(mt_rand(1, 2) === 1) {
                    return ItemManager::getSkinScroll("permafrost_pickaxe")->toItem()->setCount(1);
                } else {
                    return ItemManager::getSkinScroll("permafrost_cinqueda")->toItem()->setCount(1);
                }
            }, 100),
            new Reward("2x Item Nametags", function(NexusPlayer $player): Item {
                return (new ItemNameTag())->toItem()->setCount(2);
            }, 100),
        ];
        $treasureItems = [
            new Reward("1-2x Vulkarion G-Kit Flares", function(NexusPlayer $player): Item {
                return (new GKitFlare(Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName("Vulkarion")))->toItem()->setCount(mt_rand(1, 2));
            }, 100),
            new Reward("20-40 Cosmic Tokens", function(NexusPlayer $player): Item {
                return (new Token())->toItem()->setCount(mt_rand(20, 40));
            }, 100),
            new Reward("2x Legendary Contrabands", function(NexusPlayer $player): Item {
                return (new Contraband(Rarity::LEGENDARY))->toItem()->setCount(2);
            }, 100),
            new Reward("30-80 Legendary OR Godly Shards", function(NexusPlayer $player): Item {
                return (new Shard([Rarity::LEGENDARY, Rarity::GODLY][mt_rand(0, 1)]))->toItem()->setCount(mt_rand(30, 80));
            }, 100),
            new Reward("Random Rank!", function(NexusPlayer $player): Item {
                $rank = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(mt_rand(1, 5));
                return (new RankNote($rank))->toItem()->setCount(1);
            }, 100),
            new Reward("1x Santa Set Piece", function(NexusPlayer $player): Item {
                $loot = Nexus::getInstance()->getGameManager()->getItemManager()->getSetManager()->getSet("santa")->compileSet();
                return $loot[array_rand($loot)]->setCount(1);
            }, 100)
        ];
        $bonus = [
            new Reward("3x Random G-Kit Flares", function(NexusPlayer $player): Item {
                return (new GKitFlare(null, false))->toItem()->setCount(3);
            }, 100),
            new Reward("2,000,000 - 5,000,000 Cosmic Energy", function(NexusPlayer $player): Item {
                return (new Energy(mt_rand(2000000, 5000000)))->toItem()->setCount(1);
            }, 100),
            new Reward("High-Tier Rank", function(NexusPlayer $player): Item {
                $rank = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(mt_rand(3, 5));
                return (new RankNote($rank))->toItem()->setCount(1);
            }, 25)
        ];
        parent::__construct(self::DECEMBER, 2022, $coloredName, $adminItems, $cosmetics, $treasureItems, $bonus);
    }
}