<?php
declare(strict_types=1);

namespace core\game\rewards\types\shard;

use core\game\item\types\custom\Absorber;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\ChargeOrbSlot;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\CrudeOre;
use core\game\item\types\custom\EnchantmentDust;
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

class SimpleShard extends ShardRewards {

    /**
     * SimpleShard constructor.
     */
    public function __construct() {
        $rewards = [
            new Reward("Simple Contraband", function(?NexusPlayer $player): Item {
                $item = (new Contraband(Rarity::SIMPLE))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 25),
            new Reward("Wooden Pickaxe", function(?NexusPlayer $player): Item {
                $item = ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE, 0, 1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Energy Absorber", function(?NexusPlayer $player): Item {
                $item = (new Absorber())->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Enchant Dust", function(?NexusPlayer $player): Item {
                $item = (new EnchantmentDust(1))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Charge Orb", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrb(mt_rand(1, 2)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Charge Orb Slot", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrbSlot())->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 75),
            new Reward("Stash of ores", function(?NexusPlayer $player): Item {
                if(mt_rand(1, 2) === 1) {
                    $item = ItemFactory::getInstance()->get(ItemIds::IRON_ORE, 0, 64);
                }
                else {
                    $item = ItemFactory::getInstance()->get(ItemIds::COAL_ORE, 0, 64);
                }
                if($player !== null) {
                    $player->addItem($item, true);
                }
                return $item;
            }, 10000),
            new Reward("Crude Ore", function(?NexusPlayer $player): Item {
                $item = (new CrudeOre(Rarity::SIMPLE))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 15),
            new Reward("Ore Generator", function(?NexusPlayer $player): Item {
                $item = (new OreGenerator(VanillaBlocks::COAL_ORE()))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 15),
            new Reward("5k - 15k Energy", function(?NexusPlayer $player): Item {
                $item = (new Energy(5000 * mt_rand(1, 3)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->addItem($item, true);
                }
                return $item;
            }, 450)
        ];
        parent::__construct(Rarity::SIMPLE, $rewards);
    }
}