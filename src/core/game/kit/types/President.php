<?php

declare(strict_types = 1);

namespace core\game\kit\types;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\mask\Mask;
use core\game\item\slotbot\SlotBotTicket;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\EnchantmentOrb;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\ExecutiveBooster;
use core\game\item\types\custom\GKitItemGenerator;
use core\game\item\types\custom\HeroicToken;
use core\game\item\types\custom\MeteorFlare;
use core\game\item\types\custom\MultiMask;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\MysteryTrinketBox;
use core\game\item\types\custom\OreGenBooster;
use core\game\item\types\custom\RandomGKit;
use core\game\item\types\custom\Shard;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\Rarity;
use core\game\kit\Kit;
use core\game\rewards\RewardsManager;
use core\game\rewards\types\lootbox\CrashLandingLootbox;
use core\game\rewards\types\lootbox\PrisonBreakLootbox;
use core\game\rewards\types\lootbox\SafetyFirstLootbox;
use core\game\rewards\types\monthly\seventeen\BackToSchoolCrate;
use core\game\rewards\types\MonthlyRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\utils\TextFormat;

class President extends Kit {

    /**
     * Heroic constructor.
     */
    public function __construct() {
        parent::__construct("President", TextFormat::RED, 604800);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::RED. "President" . TextFormat::RESET;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return array
     */
    public function giveTo(NexusPlayer $player, bool $give = true): array {
        $items = [];
        $level = $player->getDataSession()->getTotalXPLevel();
        $items[] = (new Energy(mt_rand(3000000, 6000000)))->toItem()->setCount(1);
        $items[] = (new SlotBotTicket("Normal"))->toItem()->setCount(mt_rand(4, 9));
        $items[] = (new RandomGKit())->toItem()->setCount(1);
        $rarity = ItemManager::getRarityByLevel($level);
        $items[] = (new Shard($rarity))->toItem()->setCount(mt_rand(128, 192));
        for($i = 0; $i < 5; $i++) {
            switch(mt_rand(1, 16)) {
                case 1:
                    $items[] = (new EnergyBooster(1 + (mt_rand(5, 35) * 0.1), mt_rand(30, 60)))->toItem()->setCount(1);
                    break;
                case 2:
                    $items[] = (new OreGenBooster(mt_rand(60, 420)))->toItem()->setCount(1);
                    break;
                case 3:
                    $items[] = (new XPBooster(1 + (mt_rand(5, 35) * 0.1), mt_rand(30, 60)))->toItem()->setCount(1);
                    break;
                case 4:
                    $items[] = (new Contraband(Rarity::GODLY))->toItem()->setCount(2);
                    break;
                case 5:
                    $items[] = (new ExecutiveBooster(mt_rand(2, 5)))->toItem()->setCount(1);
                    break;
                case 6:
                    $items[] = (new BlackScroll(100))->toItem()->setCount(3);
                    break;
                case 7:
                    $items[] = (new WhiteScroll())->toItem()->setCount(1); // ?
                    break;
                case 8:
                    $items[] = (new MeteorFlare())->toItem()->setCount(3);
                    break;
                case 9:
                    $items[] = (new MysteryTrinketBox())->toItem()->setCount(1);
                    break;
                case 10:
                    $items[] = (new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::WARP_MINER), mt_rand(1, 3)), 100))->toItem()->setCount(1);
                    break;
                case 11:
                    $items[] = (new ChargeOrb(mt_rand(15, 25)))->toItem()->setCount(1);
                    break;
                case 12:
                    $items[] = (new MultiMask([Mask::GUARD, Mask::PRISONER]))->toItem()->setCount(1);
                    break;
                case 13:
                    $options = [MonthlyRewards::BACK_TO_SCHOOL, MonthlyRewards::JANUARY];
                    $items[] = (new AethicCrate($options[array_rand($options)], 2022))->toItem()->setCount(1);
                    break;
                case 14:
                    $options[] = (new MysteryEnchantmentBook(Rarity::GODLY))->toItem()->setCount(5);
                    break;
                case 15:
                    $options[] = (new MysteryEnchantmentBook(Rarity::EXECUTIVE))->toItem()->setCount(1);
                    break;
                case 16:
                    $lootboxes = [RewardsManager::CRASH_LANDING, RewardsManager::PRISON_BREAK, RewardsManager::SAFETY_FIRST];
                    $options[] = (new \core\game\item\types\custom\Lootbox($lootboxes[array_rand($lootboxes)]))->toItem()->setCount(1);
                    break; // TODO: More, maybe even skins?
            }
        }
//        for($i = 0; $i < 5; $i++) {
//            switch(mt_rand(1, 4)) {
//                case 1:
//                    $items[] = (new Contraband(Rarity::ULTIMATE))->toItem()->setCount(1);
//                    break;
//                case 2:
//                    $items[] = (new Contraband(Rarity::LEGENDARY))->toItem()->setCount(1);
//                    break;
//                case 3:
//                    $items[] = (new Contraband(Rarity::GODLY))->toItem()->setCount(1);
//                    break;
//                case 4:
//                    $items[] = (new RandomGKit())->toItem()->setCount(1);
//                    break;
//            }
//        }
//        switch(mt_rand(1, 2)) {
//            case 1:
//                $items[] = (new WhiteScroll())->toItem()->setCount(1);
//                break;
//            case 2:
//                $items[] = (new BlackScroll(mt_rand(1, 100)))->toItem()->setCount(1);
//                break;
//        }
//        $items[] = (new XPBooster(1 + (mt_rand(0, 30) * 0.1), mt_rand(10, 60)))->toItem()->setCount(1);
//        $items[] = (new OreGenBooster(mt_rand(15, 300)))->toItem()->setCount(1);
//        $items[] = (new EnergyBooster(1 + (mt_rand(0, 30) * 0.1), mt_rand(10, 60)))->toItem()->setCount(1);
//        $items[] = (new GKitItemGenerator())->toItem()->setCount(mt_rand(1, 3));
        if($give) {
            foreach($items as $item) {
                if($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                }
                else {
                    if($item->getCount() > 64) {
                        $item->setCount(64);
                    }
                    $player->getWorld()->dropItem($player->getPosition(), $item);
                }
            }
        }
        return $items;
    }
}