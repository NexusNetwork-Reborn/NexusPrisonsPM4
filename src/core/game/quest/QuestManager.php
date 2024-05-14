<?php
declare(strict_types=1);

namespace core\game\quest;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\mask\Mask;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\EnchantmentOrb;
use core\game\item\types\custom\EnchantmentPage;
use core\game\item\types\custom\ExecutiveBooster;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\HomeExpansion;
use core\game\item\types\custom\MeteorFlare;
use core\game\item\types\custom\MultiMask;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\MysteryTrinketBox;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\custom\RandomGKit;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\customies\ItemSkinScroll;
use core\game\item\types\Rarity;
use core\game\quest\types\DamageByMeteorQuest;
use core\game\quest\types\EarnEnergyQuest;
use core\game\quest\types\FindShardsQuest;
use core\game\quest\types\GainMomentumQuest;
use core\game\quest\types\KillQuest;
use core\game\quest\types\LevelUpSatchelQuest;
use core\game\quest\types\MineMeteoriteQuest;
use core\game\quest\types\MineQuest;
use core\game\quest\types\PrestigePickaxeQuest;
use core\game\quest\types\UseEnchantOrbQuest;
use core\Nexus;
use pocketmine\item\enchantment\EnchantmentInstance;

class QuestManager {

    /** @var Nexus */
    private $core;

    /** @var Quest[] */
    private $quests = [];

    /** @var QuestShopItem[] */
    private $questShop = [];

    /** @var QuestShopItem[] */
    private $activeItems = [];

    /**
     * PassManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
        $core->getServer()->getPluginManager()->registerEvents(new QuestListener($core), $core);
    }

    public function init(): void {
        $this->addQuest(new MineQuest("Excavator", "Mine 20,000 blocks", Rarity::SIMPLE, 20000));
        $this->addQuest(new MineQuest("Excavator II", "Mine 40,000 blocks", Rarity::UNCOMMON, 40000));
        $this->addQuest(new MineQuest("Excavator III", "Mine 80,000 blocks", Rarity::ELITE, 80000));
        $this->addQuest(new MineQuest("Excavator IV", "Mine 120,000 blocks", Rarity::ULTIMATE, 120000));
        $this->addQuest(new MineQuest("Excavator V", "Mine 180,000 blocks", Rarity::LEGENDARY, 180000));
        $this->addQuest(new MineQuest("Excavator VI", "Mine 250,000 blocks", Rarity::GODLY, 250000));
        $this->addQuest(new LevelUpSatchelQuest("Backpack", "Level up 1 satchel", Rarity::ULTIMATE, 1));
        $this->addQuest(new UseEnchantOrbQuest("Enchantment Apprentice", "Use 1 Enchantment Orb", Rarity::UNCOMMON, 1));
        $this->addQuest(new KillQuest("Warrior", "Kill 10 players", Rarity::SIMPLE, 10));
        $this->addQuest(new FindShardsQuest("Hoarder", "Earn a total of 50 shards from mining", Rarity::ULTIMATE, 50));
        $this->addQuest(new EarnEnergyQuest("Wait What Time Is It?", "Earn a total of 10,000,000 energy from mining", Rarity::ULTIMATE, 10000000));
        $this->addQuest(new PrestigePickaxeQuest("Prestige!", "Prestige 1 pickaxe", Rarity::ULTIMATE, 1));
        $this->addQuest(new DamageByMeteorQuest("Ouch That Hurt", "Have 1 meteor/meteorite fall on top of you", Rarity::SIMPLE, 1));
        $this->addQuest(new MineMeteoriteQuest("Meteorite Maniac", "Mine 750 meteorite blocks", Rarity::SIMPLE, 750));
        $this->addQuest(new GainMomentumQuest("Momentous Achievement", "Get your Momentum to 25% 1 timeMine 750 meteorite blocks", Rarity::SIMPLE, 1));
    }

    public function initQuestShopItems(): void {
        $first = Mask::ALL_MASKS[array_rand(Mask::ALL_MASKS)];
        $second = Mask::ALL_MASKS[array_rand(Mask::ALL_MASKS)];
        if($first === $second){
            self::initQuestShopItems();
            return;
        }
        $this->questShop[] = new QuestShopItem((new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::EFFICIENCY2), 6), 100))->toItem(), 60);
        $this->questShop[] = new QuestShopItem((new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::LUCKY), 4), 100))->toItem(), 120);
        $this->questShop[] = new QuestShopItem((new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::MOMENTUM), 6), 100))->toItem(), 120);
        $this->questShop[] = new QuestShopItem((new EnchantmentOrb(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::WARP_MINER), 1), 100))->toItem(), 400);
        $this->questShop[] = new QuestShopItem((new MeteorFlare())->toItem(), 80);
        $this->questShop[] = new QuestShopItem((new ChargeOrb(20))->toItem(), 100);
        $this->questShop[] = new QuestShopItem((new Contraband(Rarity::GODLY))->toItem(), 200);
        $this->questShop[] = new QuestShopItem((new RandomGKit())->toItem(), 400);
        $this->questShop[] = new QuestShopItem((new WhiteScroll())->toItem(), 25);
        $this->questShop[] = new QuestShopItem((new BlackScroll(100))->toItem(), 25);
        $this->questShop[] = new QuestShopItem((new XPBooster(2.5, 60))->toItem(), 150);
        $this->questShop[] = new QuestShopItem((new GKitFlare(null, false))->toItem(), 200);
        $this->questShop[] = new QuestShopItem((new GKitFlare(null, true))->toItem(), 100);
        $this->questShop[] = new QuestShopItem((new MysteryTrinketBox())->toItem(), 200);
        $this->questShop[] = new QuestShopItem((new MysteryEnchantmentBook(Rarity::ENERGY))->toItem(), 750);
        $this->questShop[] = new QuestShopItem((new EnchantmentPage(Enchantment::ENERGY, 5, 5))->toItem(), 250);
        $this->questShop[] = new QuestShopItem((new MysteryEnchantmentBook(Rarity::EXECUTIVE))->toItem(), 750);
        $this->questShop[] = new QuestShopItem((new EnchantmentPage(Enchantment::EXECUTIVE, 5, 5))->toItem(), 250);
        $this->questShop[] = new QuestShopItem((new PrestigeToken(5))->toItem(), 350);
        $this->questShop[] = new QuestShopItem((new MultiMask([$first, $second]))->toItem(), 350);
        $this->questShop[] = new QuestShopItem((new ExecutiveBooster(mt_rand(1, 3)))->toItem(), 150);
        $this->questShop[] = new QuestShopItem((new HomeExpansion())->toItem()->setCount(3), 300);
        if(mt_rand(1, 3) === 3) {
            $this->questShop[] = new QuestShopItem(
                ItemManager::getSkinScroll(ItemSkinScroll::ELITE[array_rand(ItemSkinScroll::ELITE)])->toItem()->setCount(1),
                400
            );
        } else {
            $this->questShop[] = new QuestShopItem(
                ItemManager::getSkinScroll(ItemSkinScroll::UNCOMMON[array_rand(ItemSkinScroll::UNCOMMON)])->toItem()->setCount(1),
                300
            );
        }
        shuffle($this->questShop);
        for($i = 0; $i < 10; $i++) {
            $this->activeItems[] = array_shift($this->questShop);
        }
    }

    /**
     * @return Quest[]
     */
    public function getQuests(): array {
        return $this->quests;
    }

    /**
     * @param string $id
     *
     * @return Quest|null
     */
    public function getQuest(string $id):?Quest {
        return $this->quests[$id] ?? null;
    }

    /**
     * @param Quest $quest
     */
    public function addQuest(Quest $quest): void {
        if(isset($this->quests[$quest->getName()])) {
            throw new QuestException("Attempt to override an existing quest with the name of: " . $quest->getName());
        }
        $this->quests[$quest->getName()] = $quest;
    }

    /**
     * @return QuestShopItem[]
     */
    public function getActiveItems(): array {
        return $this->activeItems;
    }
}