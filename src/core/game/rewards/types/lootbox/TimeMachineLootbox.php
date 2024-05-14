<?php
declare(strict_types=1);

namespace core\game\rewards\types\lootbox;

use core\game\item\ItemManager;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\SlaughterLootbag;
use core\game\rewards\RewardsManager;
use core\game\rewards\types\LootboxRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use core\game\rewards\Reward;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;
use pocketmine\item\ItemFactory;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\MeteorFlare;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\Title;
use core\game\item\types\custom\ChargeOrb;


class TimeMachineLootbox extends LootboxRewards
{

    public function __construct()
    {
        $rewards = [
            new Reward("Emerald Ore Satchel (0 / 2,304)", function (?NexusPlayer $player): Item {
                $item = (new Satchel(ItemFactory::getInstance()->get(ItemIds::EMERALD)))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 45),
            new Reward("Diamond Ore Satchel (0 / 2,304)", function (?NexusPlayer $player): Item {
                $item = (new Satchel(ItemFactory::getInstance()->get(ItemIds::DIAMOND)))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 55),
            new Reward("XP Booster", function (?NexusPlayer $player): Item {
                $item = (new XPBooster(3, 15))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 55),
            new Reward("Godly Enchant", function (?NexusPlayer $player): Item {
                $item = (new MysteryEnchantmentBook('Godly'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 20),
            new Reward("Executioner GKit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Executioner');
                $item = (new GKitFlare($kit, false))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            new Reward("3x Legendary Contraband", function (?NexusPlayer $player): Item {
                $item = (new Contraband('Legendary'))->toItem()->setCount(3);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Meteor Flare", function (?NexusPlayer $player): Item {
                $item = (new MeteorFlare())->toItem()->setCount(5);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 80),
            new Reward("Slaughter Lootbag", function (?NexusPlayer $player): Item {
                $item = (new SlaughterLootbag())->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 80),
            new Reward("Relic Ikalaka Item Skin", function (?NexusPlayer $player): Item {
                $item = ItemManager::getSkinScroll("relic_ikalaka")->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25),
            new Reward("Adra Pickaxe Item Skin", function (?NexusPlayer $player): Item {
                $item = ItemManager::getSkinScroll("adra_pickaxe")->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25)
        ];
        $jackpot = [
            new Reward("Lootbox: Code Green", function (?NexusPlayer $player): Item {
                $item = (new Lootbox("Code Green"))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10),
            new Reward("Back To School 2022", function (?NexusPlayer $player): Item {
                $item = (new AethicCrate('Back To School', 2022))->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5)
        ];
        $bonus = [
            new Reward("Title #TheBestTitleOnnexusPrisons", function (?NexusPlayer $player): Item {
                $item = (new Title('TheBestTitleOnnexusPrisons'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50),
            new Reward("+15% Charge Orb", function (?NexusPlayer $player): Item {
                $item = (new ChargeOrb(15))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50)
        ];
        $coloredName =  TextFormat::AQUA . TextFormat::BOLD . "Time " . TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Machine";
        $lore = "Take control of your time!";
        $rewardCount = 4;
        $display = VanillaBlocks::EMERALD()->asItem();
        parent::__construct(TextFormat::clean("Time Machine"), $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }

}