<?php
declare(strict_types=1);

namespace core\game\rewards\types\shard;

use core\game\item\types\custom\Absorber;
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

class LegendaryShard extends ShardRewards {

    /**
     * LegendaryShard constructor.
     */
    public function __construct() {
        $rewards = [
            new Reward("Legendary Contraband", function(?NexusPlayer $player): Item {
                $item = (new Contraband(Rarity::LEGENDARY))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10),
            new Reward("Diamond Pickaxe", function(?NexusPlayer $player): Item {
                $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE, 0, 1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Energy Absorber", function(?NexusPlayer $player): Item {
                $item = (new Absorber())->toItem()->setCount(5);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Enchant Dust", function(?NexusPlayer $player): Item {
                $item = (new EnchantmentDust(mt_rand(5, 9)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Enchant Re-Roll", function(?NexusPlayer $player): Item {
                $item = (new EnchantmentReroll())->toItem()->setCount(5);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Charge Orb", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrb(mt_rand(6, 9)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Charge Orb Slot", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrbSlot())->toItem()->setCount(5);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Stash of ores", function(?NexusPlayer $player): Item {
                if(mt_rand(1, 2) === 1) {
                    $item = ItemFactory::getInstance()->get(ItemIds::GOLD_ORE, 0, 64);
                }
                else {
                    $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_ORE, 0, 64);
                }
                if($player !== null) {
                    $player->addItem($item, true);
                }
                return $item;
            }, 10000),
            new Reward("Crude Ore", function(?NexusPlayer $player): Item {
                $item = (new CrudeOre(Rarity::LEGENDARY))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5),
            new Reward("Ore Generator", function(?NexusPlayer $player): Item {
                $item = (new OreGenerator(VanillaBlocks::GOLD_ORE()))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5),
            new Reward("40k - 80k Energy", function(?NexusPlayer $player): Item {
                $item = (new Energy(10000 * mt_rand(4, 8)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->addItem($item, true);
                }
                return $item;
            }, 450),
        ];
        parent::__construct(Rarity::LEGENDARY, $rewards);
    }
}