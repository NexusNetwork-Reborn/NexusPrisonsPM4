<?php
declare(strict_types=1);

namespace core\game\rewards\types\monthly\seventeen;

use core\game\item\ItemManager;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Cosmetic;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\ItemNameTag;
use core\game\item\types\custom\MeteorFlare;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\Shard;
use core\game\item\types\custom\SpaceVisor;
use core\game\item\types\custom\Title;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\Rarity;
use core\game\rewards\Reward;
use core\game\rewards\types\MonthlyRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class AugustCrate extends MonthlyRewards {

    /**
     * HolidayCrate constructor.
     */
    public function __construct() {
        //$coloredName = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Back 2 School 2017";
        $coloredName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "COSMIC CRATE: " . TextFormat::DARK_GREEN . "August 2022";
        $adminItems = [
            new Reward("Fidget Spinner", function(NexusPlayer $player): Item {
                $display = VanillaItems::CLOCK();
                $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::BLUE . "Fidget" . TextFormat::RESET . TextFormat::RED . " Spinner";
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Watch me spin by myself!";
                return (new Cosmetic($display, $name, $lore, true))->toItem();
            }, 100)
        ];
        $cosmetics = [
            new Reward("Custom Title", function(NexusPlayer $player): Item {
                return (new Title("Depression"))->toItem();
            }, 100),
            new Reward("1-2x Item Nametags", function(NexusPlayer $player): Item {
                return (new ItemNameTag())->toItem()->setCount(mt_rand(1, 2));
            }, 100),
            new Reward("Space Visor", function(NexusPlayer $player): Item {
                return (new SpaceVisor($player->getName()))->toItem();
            }, 100),
            new Reward("Godly Item Skin", function(NexusPlayer $player): Item {
                if(mt_rand(1, 2) === 1) {
                    return ItemManager::getSkinScroll("shadowflame_pickaxe")->toItem()->setCount(1);
                } else {
                    return ItemManager::getSkinScroll("nebonite_gladius")->toItem()->setCount(1);
                }
            }, 100),
        ];
        $treasureItems = [
            new Reward("High-Tier Contraband", function(NexusPlayer $player): Item {
                return (new Contraband(Rarity::GODLY))->toItem();
            }, 100),
            new Reward("50-80 High-tier Shards", function(NexusPlayer $player): Item {
                return (new Shard(Rarity::LEGENDARY))->toItem()->setCount(mt_rand(50, 80));
            }, 100),
            new Reward("Charge Orb +40% x10-15", function(NexusPlayer $player): Item {
                return (new ChargeOrb(40))->toItem()->setCount(mt_rand(10, 15));
            }, 100),
            new Reward("1-2x Colossus G-Kit Flares", function(NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName("Heroic Colossus");
                return (new GKitFlare($kit, true))->toItem()->setCount(mt_rand(1, 2));
            }, 100),
            new Reward("1-2x White Scrolls", function(NexusPlayer $player): Item {
                return (new WhiteScroll())->toItem()->setCount(mt_rand(1, 2));
            }, 100)
        ];
        $bonus = [
            new Reward("5x High-Tier Contraband", function(NexusPlayer $player): Item {
                return (new Contraband(Rarity::LEGENDARY))->toItem()->setCount(5);
            }, 100),
            new Reward("3x Meteor Flare", function(NexusPlayer $player): Item {
                return (new MeteorFlare())->toItem()->setCount(3);
            }, 100),
            new Reward("1,000,000 - 4,000,000 Energy", function(NexusPlayer $player): Item {
                return (new Energy(mt_rand(1000000, 4000000)))->toItem()->setCount(1);
            }, 100),
            new Reward("High Tier Rank", function(NexusPlayer $player): Item {
                $rank = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(mt_rand(2, 4));
                return (new RankNote($rank))->toItem()->setCount(1);
            }, 25)
        ];
        parent::__construct(self::AUGUST, 2022, $coloredName, $adminItems, $cosmetics, $treasureItems, $bonus);
    }
}