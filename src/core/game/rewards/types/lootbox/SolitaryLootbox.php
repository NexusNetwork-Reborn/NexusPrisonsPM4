<?php
declare(strict_types=1);

namespace core\game\rewards\types\lootbox;

use core\game\item\enchantment\EnchantmentManager;
use core\game\rewards\types\LootboxRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use core\game\rewards\Reward;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use core\game\item\types\custom\ItemNameTag;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\HomeExpansion;
use core\game\item\types\custom\VaultExpansion;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\Title;
use core\game\item\types\custom\ChargeOrb;


class SolitaryLootbox extends LootboxRewards
{

    public function __construct()
    {
        $rewards = [
            new Reward("Item Nametag", function (?NexusPlayer $player): Item {
                $item = (new ItemNameTag())->toItem()->setCount(3);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("2,500,000 Energy", function (?NexusPlayer $player): Item {
                $item = (new Energy(2500000))->toItem()->setCount(1);
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
            new Reward("Godly Enchant", function (?NexusPlayer $player): Item {
                $item = (new MysteryEnchantmentBook('Godly'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 20),
            /*new Reward("Momentum VI (100%)", function (?NexusPlayer $player): Item {
                $args = [10, 4, 100, 0]; // ID, Level, Success Chance, Destroy Chance
                $enchantment = new EnchantmentInstance(EnchantmentManager::getEnchantment($args[0]), $args[1]);
                $item = (new EnchantmentBook($enchantment, $args[2], $args[3]))->toItem();
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 40),*/
            new Reward("Blacksmith GKit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Blacksmith');
                $item = (new GKitFlare($kit, false))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            new Reward("+2 Homes", function (?NexusPlayer $player): Item {
                $item = (new HomeExpansion())->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 85),
            new Reward("+2 PV Rows", function (?NexusPlayer $player): Item {
                $item = (new VaultExpansion())->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 85)
        ];
        $jackpot = [
            new Reward("Pickaxe Prestige Token IV", function (?NexusPlayer $player): Item {
                $item = (new PrestigeToken(4))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem((new PrestigeToken(4))->toItem());
                }
                return $item;
            }, 10),
            new Reward("Back To School 2017", function (?NexusPlayer $player): Item {
                $item = (new AethicCrate('Back To School', 2022))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5)
        ];
        $bonus = [
            new Reward("Title #Rekt", function (?NexusPlayer $player): Item {
                $item = (new Title('#Rekt'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("+15% Charge Orb", function (?NexusPlayer $player): Item {
                $item = (new ChargeOrb(15))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100)
        ];
        $coloredName = TextFormat::STRIKETHROUGH . TextFormat::BOLD . TextFormat::BLACK . "Solitary";
        $lore = "Break free from your chains!?";
        $rewardCount = 4;
        $display = VanillaBlocks::EMERALD()->asItem();
        parent::__construct("Solitary", $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }

}