<?php
declare(strict_types=1);

namespace core\game\rewards\types\shard;

use core\game\item\types\custom\Absorber;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\ChargeOrbSlot;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\CrudeOre;
use core\game\item\types\custom\EnchantmentDust;
use core\game\item\types\custom\EnchantmentReroll;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\OreGenerator;
use core\game\item\types\Rarity;
use core\game\rewards\Reward;
use core\game\rewards\types\ShardRewards;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

class GodlyShard extends ShardRewards {

    /**
     * GodlyShard constructor.
     */
    public function __construct() {
        $rewards = [
            new Reward("Godly Contraband", function(?NexusPlayer $player): Item {
                $item = (new Contraband(Rarity::GODLY))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5),
            new Reward("Energy Absorber", function(?NexusPlayer $player): Item {
                $item = (new Absorber())->toItem()->setCount(6);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Enchant Dust", function(?NexusPlayer $player): Item {
                $item = (new EnchantmentDust(mt_rand(7, 13)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Enchant Re-Roll", function(?NexusPlayer $player): Item {
                $item = (new EnchantmentReroll())->toItem()->setCount(6);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Charge Orb", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrb(mt_rand(8, 12)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Black Scroll", function(?NexusPlayer $player): Item {
                $item = (new BlackScroll(mt_rand(1, 100)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25),
            new Reward("Charge Orb Slot", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrbSlot())->toItem()->setCount(6);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Stash of ores", function(?NexusPlayer $player): Item {
                if(mt_rand(1, 2) === 1) {
                    $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_ORE, 0, 64);
                }
                else {
                    $item = ItemFactory::getInstance()->get(ItemIds::EMERALD_ORE, 0, 64);
                }
                if($player !== null) {
                    $player->addItem($item, true);
                }
                return $item;
            }, 10000),
            new Reward("Crude Ore", function(?NexusPlayer $player): Item {
                $item = (new CrudeOre(Rarity::GODLY))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 2),
            new Reward("Ore Generator", function(?NexusPlayer $player): Item {
                $item = (new OreGenerator(VanillaBlocks::DIAMOND_ORE()))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 2),
            new Reward("60k - 120k Energy", function(?NexusPlayer $player): Item {
                $item = (new Energy(10000 * mt_rand(6, 12)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->addItem($item, true);
                }
                return $item;
            }, 450),
        ];
        parent::__construct(Rarity::GODLY, $rewards);
    }
}