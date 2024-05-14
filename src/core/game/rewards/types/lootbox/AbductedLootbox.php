<?php
declare(strict_types=1);

namespace core\game\rewards\types\lootbox;

use core\game\item\types\custom\Lootbox;
use core\game\rewards\types\LootboxRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use core\game\rewards\Reward;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\RandomGKit;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\VaultExpansion;
use core\game\item\types\custom\Mask;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\Title;


class AbductedLootbox extends LootboxRewards
{

    public function __construct()
    {
        $rewards = [
            new Reward("6,000,000 Energy", function (?NexusPlayer $player): Item {
                $item = (new Energy(6000000))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 30),
            new Reward("Random GKit Level Up", function (?NexusPlayer $player): Item {
                $item = (new RandomGKit())->toItem()->setCount(2);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            new Reward("Mystery Contraband", function (?NexusPlayer $player): Item {
                $args = ["Simple", "Uncommon", "Elite", "Ultimate", "Legendary", "Godly"];
                $item = (new Contraband($args[mt_rand(0, 5)]))->toItem()->setCount(3);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 60),
            new Reward("Mystery XP Booster", function (?NexusPlayer $player): Item {
                $item = (new XPBooster(mt_rand(2, 3), mt_rand(5, 10)))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 20),
            new Reward("Mystery Energy Booster", function (?NexusPlayer $player): Item {
                $item = (new EnergyBooster(mt_rand(2, 3), mt_rand(5, 10)))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 20),
            new Reward("Hero GKit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Hero');
                $item = (new GKitFlare($kit, false))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            new Reward("Cyborg GKit Flare", function (?NexusPlayer $player): Item {
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName('Cyborg', 1);
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
            }, 85)
        ];
        $jackpot = [
            new Reward("Prisoner Mask", function (?NexusPlayer $player): Item {
                $item = (new Mask(\core\game\item\mask\Mask::PRISONER))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            new Reward("Guard Mask", function (?NexusPlayer $player): Item {
                $item = (new Mask(\core\game\item\mask\Mask::GUARD))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 65),
            new Reward("Lootbox: UFO", function (?NexusPlayer $player): Item {
                $item = (new Lootbox("UFO", time() + 604800))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10),
            new Reward("Heroic Rank Crystal: <V+>", function (?NexusPlayer $player): Item {
                $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(6)))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5)
        ];
        $bonus = [
            new Reward("Title Abducted", function (?NexusPlayer $player): Item {
                $item = (new Title('Abducted'))->toItem()->setCount(1);
                if ($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50)
        ];
        $coloredName = TextFormat::BOLD . TextFormat::AQUA . "Abducted";
        $lore = "You saved our lives we are grateful!!";
        $rewardCount = 4;
        $display = VanillaBlocks::EMERALD()->asItem();
        parent::__construct("Abducted", $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }

}