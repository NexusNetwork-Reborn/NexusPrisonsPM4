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
use core\game\item\types\custom\Shard;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Title;


class LockdownLootbox extends LootboxRewards
{

    public function __construct()
    {
        $rewards = [
            new Reward("Godly Shard", function (?NexusPlayer $player): Item {
                $item = (new Shard('Godly'))->toItem()->setCount(64);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 55),
            new Reward("3,000,000 Energy", function (?NexusPlayer $player): Item {
                $item = (new Energy(3000000))->toItem()->setCount(1);
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
            new Reward("Lucky IV (100%)", function (?NexusPlayer $player): Item {
                $args = [10, 3, 75, 25]; // ID, Level, Success Chance, Destroy Chance
                $enchantment = new EnchantmentInstance(EnchantmentManager::getEnchantment($args[0]), $args[1]);
                $item = (new EnchantmentBook($enchantment, $args[2], $args[3]))->toItem();
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 40),
            new Reward("Grim Reaper G-Kit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Grim Reaper');
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
            }, 75)
        ];
        $jackpot = [
            new Reward("Godly Contraband", function (?NexusPlayer $player): Item {
                $item = (new Contraband('Godly'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10),
            new Reward("15,000,000 Energy", function (?NexusPlayer $player): Item {
                $item = (new Energy(15000000))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 1)
        ];
        $bonus = [
            new Reward("Title Jackpot Junkie", function (?NexusPlayer $player): Item {
                $item = (new Title('Jackpot Junkie'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50)
        ];
        $coloredName = TextFormat::DARK_RED . TextFormat::BOLD . "Lockdown";
        $lore = "At least they didn't find my lootbox!?";
        $rewardCount = 4;
        $display = VanillaBlocks::EMERALD()->asItem();
        parent::__construct("Lockdown", $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }

}