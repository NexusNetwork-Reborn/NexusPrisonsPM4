<?php
declare(strict_types=1);

namespace core\game\rewards\types\contraband;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\ChargeOrbSlot;
use core\game\item\types\custom\EnchantmentReroll;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\HomeExpansion;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\ShowcaseExpansion;
use core\game\item\types\custom\VaultExpansion;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Pickaxe;
use core\game\rewards\Reward;
use core\game\rewards\types\ContrabandRewards;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class SimpleContraband extends ContrabandRewards {

    /**
     * SimpleContraband constructor.
     */
    public function __construct() {
        $rewards = [
            new Reward("Mystery Simple Enchant", function(?NexusPlayer $player): Item {
                $item = (new MysteryEnchantmentBook(Rarity::SIMPLE))->toItem()->setCount(3);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10000),
            new Reward("Enchant Re-Rolls", function(?NexusPlayer $player): Item {
                $item = (new EnchantmentReroll())->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10000),
            new Reward("Charge Orb", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrb(mt_rand(1, 3)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5000),
            new Reward("Charge Orb Slot", function(?NexusPlayer $player): Item {
                $item = (new ChargeOrbSlot())->toItem()->setCount(3);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5000),
            new Reward("Plutonian Rank", function(?NexusPlayer $player): Item {
                $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::NOBLE)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 5),
            new Reward("16k - 64k Energy", function(?NexusPlayer $player): Item {
                $item = (new Energy(16000 * mt_rand(1, 4)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->addItem($item, true);
                }
                return $item;
            }, 10000),
            new Reward("$4,000 - $16,000", function(?NexusPlayer $player): Item {
                $item = (new MoneyNote(4000 * mt_rand(1, 4)))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10000),
            new Reward("Black/White Scrolls", function(?NexusPlayer $player): Item {
                if(mt_rand(1, 2) === 1) {
                    $item = (new WhiteScroll())->toItem()->setCount(1);
                }
                else {
                    $item = (new BlackScroll(mt_rand(1, 10)))->toItem()->setCount(1);
                }
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10000),
            new Reward("Satchel", function(?NexusPlayer $player): Item {
                if(mt_rand(1, 2) === 1) {
                    $item = (new Satchel(ItemFactory::getInstance()->get(ItemIds::COAL)))->toItem()->setCount(1);
                }
                else {
                    $item = (new Satchel(ItemFactory::getInstance()->get(ItemIds::COAL_ORE)))->toItem()->setCount(1);
                }
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 7500),
            new Reward("Mystery G-Kit Flare", function(?NexusPlayer $player): Item {
                $item = (new GKitFlare(null, false))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 3),
            new Reward("Mystery G-Kit Fractured Flare", function(?NexusPlayer $player): Item {
                $item = (new GKitFlare(null, true))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 6),
            new Reward("Home/PV/Showcase Expanders", function(?NexusPlayer $player): Item {
                if(mt_rand(1, 2) === 1) {
                    $item = (new HomeExpansion())->toItem()->setCount(1);
                }
                elseif(mt_rand(1, 10) === 1) {
                    $item = (new VaultExpansion())->toItem()->setCount(1);
                }
                else {
                    $item = (new ShowcaseExpansion())->toItem()->setCount(1);
                }
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Wooden Weapons/Tools", function(?NexusPlayer $player): Item {
                $limit = 5 * mt_rand(2, 3);
                if(mt_rand(1, 2) === 1) {
                    if(mt_rand(1, 2) === 1) {
                        $item = ItemFactory::getInstance()->get(ItemIds::WOODEN_SWORD, 0, 1);
                        $slot = Enchantment::SLOT_SWORD;
                    }
                    else {
                        $item = ItemFactory::getInstance()->get(ItemIds::WOODEN_AXE, 0, 1);
                        $slot = Enchantment::SLOT_AXE;
                    }
                    for($i = 1; $i <= $limit; $i++) {
                        $enchant = EnchantmentManager::getRandomFightingEnchantment(Enchantment::GODLY, $slot, true);
                        $level = 1;
                        if($item->hasEnchantment($enchant)) {
                            $level += $item->getEnchantmentLevel($enchant);
                            if($level > $enchant->getMaxLevel()) {
                                $level = $enchant->getMaxLevel();
                            }
                        }
                        $item->addEnchantment(new EnchantmentInstance($enchant, $level));
                    }
                }
                else {
                    $item = ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE, 0, 1);
                    for($i = 1; $i <= $limit; $i++) {
                        $enchant = EnchantmentManager::getRandomMiningEnchantment(Enchantment::LEGENDARY, Enchantment::SLOT_PICKAXE, true);
                        $level = 1;
                        if($item->hasEnchantment($enchant)) {
                            $level += $item->getEnchantmentLevel($enchant);
                            if($level > $enchant->getMaxLevel()) {
                                $level = $enchant->getMaxLevel();
                            }
                        }
                        $item->addEnchantment(new EnchantmentInstance($enchant, $level));
                    }
                    $xp = XPUtils::levelToXP($limit, RPGManager::ENERGY_MODIFIER) + 1;
                    if($item instanceof Pickaxe) {
                        $item->addEnergy($xp);
                        $item->subtractPoints($limit);
                    }
                }
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10000),
        ];
        parent::__construct(Rarity::SIMPLE, $rewards);
    }
}