<?php

namespace core\game\slotbot;

use core\game\item\ItemManager;
use core\game\item\slotbot\SlotBotTicket;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\Mask;
use core\game\item\types\custom\MeteorFlare;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\custom\MultiMask;
use core\game\item\types\custom\MysteryTrinketBox;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\Token;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\customies\ItemSkinScroll;
use core\game\item\types\Rarity;
use core\game\rewards\RewardsManager;
use core\game\rewards\types\MonthlyRewards;
use core\Nexus;
use core\player\rank\Rank;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat as C;
use LogicException;
use pocketmine\item\Item;

class SlotBotRewardsSession{

    private static $userRegistry = [];

    // TODO: Make this more dynamic, as we can see below

    //1 = 0.5% chance
    private const MIN_CHANCE = 1, MAX_CHANCE = 200;

    /** @var Item[] */
    private $rewards = [];

    private $chancedRewards = [];
    private $rewardInvItems = [];

    private $mainReward = null;

    public function __construct()
    {
        $this->init();
    }

    public function init(){
        $first = \core\game\item\mask\Mask::ALL_MASKS[array_rand(\core\game\item\mask\Mask::ALL_MASKS)];
        $second = \core\game\item\mask\Mask::ALL_MASKS[array_rand(\core\game\item\mask\Mask::ALL_MASKS)];
        if($first === $second){
            $this->init();
            return;
        }
        //self::addReward((new Trinket(\core\game\item\trinket\Trinket::ABSORPTION_TRINKET))->toItem()->setCount(1), 20);
        //self::addReward((new Contraband(Rarity::GODLY))->toItem()->setCount(1), 10);
        $this->addReward((new MoneyNote(mt_rand(1000000, 20000000)))->toItem(), 80);
        $this->addReward((new Energy(mt_rand(3000000, 5000000)))->toItem(), 70);
        $this->addReward((new Token())->toItem()->setCount(mt_rand(32, 64)), 70);
        $this->addReward((new XPBooster(mt_rand(3, 4), mt_rand(60, 90)))->toItem()->setCount(1), 65);
        $this->addReward((new EnergyBooster(mt_rand(3, 4), mt_rand(60, 90)))->toItem()->setCount(1), 65);
        $this->addReward((new Satchel(VanillaItems::EMERALD()))->toItem(), 65);
        $this->addReward((new Satchel(VanillaItems::DIAMOND()))->toItem(), 65);
        $this->addReward((new Contraband(Rarity::ULTIMATE))->toItem()->setCount(1), 70);
        $this->addReward((new Contraband(Rarity::LEGENDARY))->toItem()->setCount(1), 65);
        $this->addReward((new Contraband(Rarity::GODLY))->toItem()->setCount(1), 60);
        $this->addReward((new Mask($first))->toItem()->setCount(1), 55);
        $this->addReward((new Mask($second))->toItem()->setCount(1), 55);
        $this->addReward((new MultiMask([$first, $second]))->toItem()->setCount(1), 40);
        $this->addReward((new MysteryTrinketBox())->toItem()->setCount(1), 50);
        $this->addReward((new GKitFlare(null, false))->toItem()->setCount(1), 35);
        $this->addReward((new MeteorFlare())->toItem()->setCount(1), 35);
        $this->addReward(ItemManager::getSkinScroll(ItemSkinScroll::UNCOMMON[array_rand(ItemSkinScroll::UNCOMMON)])->toItem()->setCount(1), 20);
        $this->addReward(ItemManager::getSkinScroll(ItemSkinScroll::ULTIMATE[array_rand(ItemSkinScroll::ULTIMATE)])->toItem()->setCount(1), 15);
        $this->addReward((new SlotBotTicket("Normal"))->toItem()->setCount(mt_rand(3, 6)), 15);

        // previous expiry for lootbox time() + 604800
        $this->addReward((new Lootbox(RewardsManager::CURRENT_LOOTBOX, Lootbox::NO_EXPIRY))->toItem()->setCount(1), 9);
        $this->addReward((new Lootbox(RewardsManager::CRASH_LANDING, Lootbox::NO_EXPIRY))->toItem()->setCount(1), 9);
        $this->addReward((new Lootbox(RewardsManager::PRISON_BREAK, Lootbox::NO_EXPIRY))->toItem()->setCount(1), 9);

        $this->addReward((new AethicCrate(MonthlyRewards::JANUARY, 2022))->toItem()->setCount(1), 7);
        $this->addReward((new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::EMPEROR_HEROIC)))->toItem()->setCount(1), 4);
        $this->setMainReward((new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::PRESIDENT)))->toItem()->setCount(1), 1);
    }

    public static function addRewardSession(string $name, SlotBotRewardsSession $registry): void {
        self::$userRegistry[$name] = $registry;
    }

    public static function getRewardSession(string $name): ?SlotBotRewardsSession{
        if(isset(self::$userRegistry[$name])){
            return self::$userRegistry[$name];
        }
        return null;
    }

    private function addReward(Item $reward, int $chance){
        $this->rewards = array_merge($this->rewards, array_fill(0, $chance, $reward));
        $this->chancedRewards[] = [$reward, $chance];
        $reward2 = clone $reward;
        $newLore = $reward2->getLore();
        $newLore[] = "\n" . C::YELLOW . "Chance: " . C::GOLD . round($chance / 9.09, 2) . "%";
        $reward2->setLore($newLore);
        $this->rewardInvItems[] = [$reward2, $chance];
    }

    private function setMainReward(Item $reward, int $chance){
        if($this->mainReward !== null){
            throw new LogicException("The main reward has already been set");
        }
        $this->mainReward = $reward;
        $this->rewards = array_merge($this->rewards, array_fill(0, $chance, $reward));
        $reward2 = clone $reward;
        $newLore = $reward2->getLore();
        $newLore[] = "\n" . C::YELLOW . "Chance: " . C::GOLD . round($chance / 9.09, 2). "%";
        $reward2->setLore($newLore);
        $this->rewardInvItems[] = [$reward2, $chance];
    }

    private function getRewards(): array{
        return $this->rewards;
    }

    public function getRandomReward() : Item{
        return $this->rewards[array_rand($this->rewards)];
    }

    public function getChancedRewards(): array{
        return $this->rewardInvItems;
    }


    public function getMainReward() : ?Item{
        return $this->mainReward;
    }

}