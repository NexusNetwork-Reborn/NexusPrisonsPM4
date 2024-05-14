<?php
declare(strict_types=1);

namespace core\game\rewards\types\lootbox;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\mask\Mask;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\ChargeOrbSlot;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Cosmetic;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\EnchantmentOrb;
use core\game\item\types\custom\EnchantmentPage;
use core\game\item\types\custom\EnchantmentReroll;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\GKitBeacon;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\HomeExpansion;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\custom\MultiMask;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\custom\RandomGKit;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\ShowcaseExpansion;
use core\game\item\types\custom\Title;
use core\game\item\types\custom\Token;
use core\game\item\types\custom\VaultExpansion;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Pickaxe;
use core\game\kit\GodKit;
use core\game\rewards\Reward;
use core\game\rewards\types\ContrabandRewards;
use core\game\rewards\types\LootboxRewards;
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
use pocketmine\utils\TextFormat;

class PrisonBreakLootbox extends LootboxRewards {

    const NAME = "Prison Break";

    /**
     * CrashLandingLootbox constructor.
     */
    public function __construct() {
        $rewards = [
            new Reward("Enchant Reroll", function(?NexusPlayer $player): Item {
                $item = (new EnchantmentReroll())->toItem()->setCount(15);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Token", function(?NexusPlayer $player): Item {
                $item = (new Token())->toItem()->setCount(16);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Warp Miner I", function(?NexusPlayer $player): Item {
                $item = (new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::WARP_MINER), 1), 100))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Efficiency VI", function(?NexusPlayer $player): Item {
                $item = (new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::EFFICIENCY2), 6), 100))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Enchanter G-Kit Flare", function(?NexusPlayer $player): Item {
                /** @var GodKit $kit */
                $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName("Heroic Enchanter");
                $item = (new GKitFlare($kit, false))->toItem()->setCount(2);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Prestige Token", function(?NexusPlayer $player): Item {
                $item = (new PrestigeToken(5))->toItem()->setCount(2);
                if($player !== null) {
                    $player->getInventory()->addItem((new PrestigeToken(5))->toItem());
                    $player->getInventory()->addItem((new PrestigeToken(5))->toItem());
                }
                return $item;
            }, 100),
            new Reward("Legendary Pages", function(?NexusPlayer $player): Item {
                $item = (new EnchantmentPage(Enchantment::LEGENDARY, 10, 10))->toItem()->setCount(10);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("Silence III", function(?NexusPlayer $player): Item {
                $enchantment = new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::SILENCE), 3);
                $item = (new EnchantmentBook($enchantment, 75, 25))->toItem();
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100)
        ];
        $jackpot = [
            new Reward("Lootbox: Crash Landing", function(?NexusPlayer $player): Item {
                $item = (new Lootbox(CrashLandingLootbox::NAME))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 10),
            new Reward("Multi-Mask(Pilgrim, Prisoner)", function(?NexusPlayer $player): Item {
                $item = (new MultiMask([Mask::PILGRIM, Mask::PRISONER]))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 50)
        ];
        $bonus = [
            new Reward("Title #YourMumsCard", function(?NexusPlayer $player): Item {
                $item = (new Title("#YourMumsCard"))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
            new Reward("3,000,000 Energy", function(?NexusPlayer $player): Item {
                $item = (new Energy(3000000))->toItem()->setCount(1);
                if($player !== null) {
                    $player->getInventory()->addItem($item);
                }
                return $item;
            }, 100),
        ];
        $coloredName = TextFormat::GREEN . TextFormat::BOLD . "Prison Break";
        $lore = "Can you break free?";
        $rewardCount = 4;
        $display = VanillaBlocks::EMERALD()->asItem();
        parent::__construct(self::NAME, $coloredName, $lore, $rewardCount, $display, $rewards, $jackpot, $bonus);
    }
}