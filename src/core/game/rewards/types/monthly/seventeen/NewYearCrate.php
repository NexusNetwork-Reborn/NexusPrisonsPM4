<?php

declare(strict_types=1);

namespace core\game\rewards\types\monthly\seventeen;

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
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Fireworks;
use core\game\rewards\Reward;
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

class NewYearCrate extends MonthlyRewards {

    public function __construct() {
        //$coloredName = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Back 2 School 2017";
        $coloredName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "COSMIC CRATE: " . TextFormat::GREEN . "New Year 2022";
//        $adminItems = [
//            new Reward("CheatSheet", function(NexusPlayer $player): Item {
//                $display = VanillaItems::BOOK();
//                $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::GRAY . "Cheat" . TextFormat::RESET . TextFormat::DARK_GRAY . "Sheet";
//                $lore = [];
//                $lore[] = "";
//                $lore[] = TextFormat::RESET . TextFormat::YELLOW . "2+2=21";
//                return (new Cosmetic($display, $name, $lore, true))->toItem();
//            }, 100),
//            new Reward("BIGol'Pencil", function(NexusPlayer $player): Item {
//                $display = VanillaItems::FEATHER();
//                $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "BIG" . TextFormat::DARK_AQUA . "ol'" . TextFormat::RED . "Pencil";
//                $lore = [];
//                $lore[] = "";
//                $lore[] = TextFormat::RESET . TextFormat::ITALIC . TextFormat::DARK_PURPLE . "Draw big, Dream big!!";
//                return (new Cosmetic($display, $name, $lore, true))->toItem();
//            }, 100)
//        ];
        $adminItems = [
            new Reward("New Year Firework", function(?NexusPlayer $player): Item {
                $display = (new Fireworks(new ItemIdentifier(ItemIds::FIREWORKS, 0), "Firework"));
                $display->addExplosion(Fireworks::TYPE_STAR, Fireworks::COLOR_GREEN);
                $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "New Year" . TextFormat::RED . " Fireworks";
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Goodbye 2021, Hello 2022!" . TextFormat::RED . " Use this firework considerably :>";
                $item = (new Cosmetic($display, $name, $lore, true))->toItem();
//                if($player !== null) {
//                    $player->getInventory()->addItem($item);
//                }
                return $item;
            }, 100),
            new Reward("Cowth Egg", function(?NexusPlayer $player): Item {
                $display = VanillaItems::EGG();
                $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Cowth's Egg";
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::ITALIC . TextFormat::AQUA . "Yes, this is Cowth's Egg." . TextFormat::GREEN . " Don't be a science denier!";
                $item = (new Cosmetic($display, $name, $lore, true))->toItem();
//                if($player !== null) {
//                    $player->getInventory()->addItem($item);
//                }
                return $item;
            }, 100)
        ];
        $cosmetics = [
            new Reward("Custom Title", function(NexusPlayer $player): Item {
                return (new Title(["FakeDev", "2022"][mt_rand(0, 1)]))->toItem();
            }, 100),
            new Reward("1-2x Item Nametags", function(NexusPlayer $player): Item {
                return (new ItemNameTag())->toItem()->setCount(mt_rand(1, 2));
            }, 100),
            new Reward("Space Visor", function(NexusPlayer $player): Item {
                return (new SpaceVisor($player->getName()))->toItem();
            }, 100)
        ];
        $treasureItems = [
            new Reward("Godly Contraband", function(NexusPlayer $player): Item {
                return (new Contraband(Rarity::GODLY))->toItem();
            }, 100),
            new Reward("50-70 High-tier Shards", function(NexusPlayer $player): Item {
                return (new Shard(Rarity::LEGENDARY))->toItem()->setCount(mt_rand(50, 70));
            }, 100),
            new Reward("Charge Orb +55% x3-9", function(NexusPlayer $player): Item {
                return (new ChargeOrb(mt_rand(8, 15)))->toItem()->setCount(mt_rand(3, 9));
            }, 100),
            new Reward("x2 Fractured G-Kit Flares", function(NexusPlayer $player): Item {
                return (new GKitFlare(null, true))->toItem()->setCount(2);
            }, 100),
            new Reward("1-2x White Scrolls", function(NexusPlayer $player): Item {
                return (new WhiteScroll())->toItem()->setCount(mt_rand(1, 2));
            }, 100),
            new Reward("1-2x Black Scrolls | 50-100%", function(NexusPlayer $player): Item {
                return (new BlackScroll(mt_rand(50, 100)))->toItem()->setCount(mt_rand(1, 2));
            }, 100),
            new Reward("5-10 Prestige Token", function (NexusPlayer $player) : Item {
                return (new PrestigeToken(mt_rand(5, 10)))->toItem()->setCount(1);
            }, 100)
        ];
        $bonus = [
            new Reward("3x Random G-Kit Flares", function(NexusPlayer $player): Item {
                return (new GKitFlare(null, false))->toItem()->setCount(3);
            }, 100),
            new Reward("1,000,000 - 3,000,000 Energy", function(NexusPlayer $player): Item {
                return (new Energy(mt_rand(1000000, 3000000)))->toItem()->setCount(1);
            }, 100),
            new Reward("High Tier Rank", function(NexusPlayer $player): Item {
                $rank = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(mt_rand(2, 4));
                return (new RankNote($rank))->toItem()->setCount(1);
            }, 25)
        ];
        parent::__construct(self::JANUARY, 2022, $coloredName, $adminItems, $cosmetics, $treasureItems, $bonus);
    }
}