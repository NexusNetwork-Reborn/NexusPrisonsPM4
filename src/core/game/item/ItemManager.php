<?php
declare(strict_types=1);

namespace core\game\item;

use core\display\animation\AnimationManager;
use core\game\item\cluescroll\ScrollManager;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\mask\Mask;
use core\game\item\mask\MaskListener;
use core\game\item\mask\types\BuffMask;
use core\game\item\mask\types\CactusMask;
use core\game\item\mask\types\CupidMask;
use core\game\item\mask\types\FireflyMask;
use core\game\item\mask\types\FocusMask;
use core\game\item\mask\types\GuardMask;
use core\game\item\mask\types\JailorMask;
use core\game\item\mask\types\PilgrimMask;
use core\game\item\mask\types\PrisonerMask;
use core\game\item\mask\types\ShadowMask;
use core\game\item\mask\types\TinkererMask;
use core\game\item\prestige\Prestige;
use core\game\item\prestige\types\ClueScrollMastery;
use core\game\item\prestige\types\EnergyMastery;
use core\game\item\prestige\types\ForgeMaster;
use core\game\item\prestige\types\Grinder;
use core\game\item\prestige\types\Hoarder;
use core\game\item\prestige\types\Inquisitive;
use core\game\item\prestige\types\MeteoriteMastery;
use core\game\item\prestige\types\OreExtractor;
use core\game\item\prestige\types\ShardMastery;
use core\game\item\prestige\types\XPMastery;
use core\game\item\sets\SetManager;
use core\game\item\slotbot\SlotBotTicket;
use core\game\item\trinket\entity\AbsorptionPotion;
use core\game\item\trinket\entity\GrapplingHook;
use core\game\item\trinket\entity\ResistancePotion;
use core\game\item\trinket\types\AbsorptionTrinket;
use core\game\item\trinket\types\GrapplingHookTrinket;
use core\game\item\trinket\types\HealingTrinket;
use core\game\item\trinket\types\ResistanceTrinket;
use core\game\item\types\custom\Absorber;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\ChargeOrbSlot;
use core\game\item\types\custom\ClueScroll;
use core\game\item\types\custom\ClueScrollCasket;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\EnchantmentDust;
use core\game\item\types\custom\EnchantmentOrb;
use core\game\item\types\custom\EnchantmentPage;
use core\game\item\types\custom\EnchantmentReroll;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\ExecutiveBooster;
use core\game\item\types\custom\ForgeFuel;
use core\game\item\types\custom\HadesLootbag;
use core\game\item\types\custom\InterdimensionalKey;
use core\game\item\types\custom\ItemNameTag;
use core\game\item\types\custom\KeyComponentA;
use core\game\item\types\custom\KeyComponentB;
use core\game\item\types\custom\KOTHLootbag;
use core\game\item\types\custom\MultiMask;
use core\game\item\types\custom\MysteryEnchantmentOrb;
use core\game\item\types\custom\MysteryTrinketBox;
use core\game\item\types\custom\OreGenBooster;
use core\game\item\types\custom\GKitBeacon;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\GKitItemGenerator;
use core\game\item\types\custom\HeroicToken;
use core\game\item\types\custom\HomeExpansion;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\MeteorFlare;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\custom\MysteryClueScroll;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\OreGenerator;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\custom\RandomGKit;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\Shard;
use core\game\item\types\custom\ShowcaseExpansion;
use core\game\item\types\custom\SkinScroll;
use core\game\item\types\custom\SlaughterLootbag;
use core\game\item\types\custom\SpaceVisor;
use core\game\item\types\custom\Title;
use core\game\item\types\custom\Token;
use core\game\item\types\custom\Trinket;
use core\game\item\types\custom\VaultExpansion;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\custom\XPBottle;
use core\game\item\types\customies\DiamondSkinnedPickaxe;
use core\game\item\types\customies\DiamondSkinnedSword;
use core\game\item\types\customies\GoldSkinnedPickaxe;
use core\game\item\types\customies\GoldSkinnedSword;
use core\game\item\types\customies\IronSkinnedPickaxe;
use core\game\item\types\customies\IronSkinnedSword;
use core\game\item\types\customies\ItemSkinScroll;
use core\game\item\types\customies\StoneSkinnedPickaxe;
use core\game\item\types\customies\StoneSkinnedSword;
use core\game\item\types\customies\WoodenSkinnedPickaxe;
use core\game\item\types\customies\WoodenSkinnedSword;
use core\game\item\types\CustomItem;
use core\game\item\types\Enchantable;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Fireworks;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Sword;
use core\level\LevelException;
use core\level\LevelManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use customiesdevs\customies\item\CustomiesItemFactory;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockToolType;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\Minecart;
use pocketmine\item\TieredTool;
use pocketmine\item\ToolTier;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function Sodium\add;

class ItemManager {

    /** @var Nexus */
    private $core;

    /** @var EnchantmentManager */
    private $enchantmentManager;

    /** @var ScrollManager */
    private $scrollManager;

    /** @var SetManager */
    private $setManager;

    /** @var string[] */
    private $items = [];

    /** @var Prestige[] */
    private $pickaxePrestiges = [];

    /** @var \core\game\item\trinket\Trinket[] */
    private $trinkets = [];

    /** @var Mask[] */
    private $masks = [];

    /** @var int[] */
    private $redeemed = [];

    /**
     * ItemManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new ItemListener($core), $core);
        $core->getServer()->getPluginManager()->registerEvents(new MaskListener($core), $core);
        $this->init();
    }

    public function init() {
        $config = new Config($this->core->getDataFolder() . "redeemed.json", Config::JSON);
        foreach($config->getAll() as $id => $value) {
            $value = (int)$value;
            if((time() - $value) < 604800) {
                $this->redeemed[(string)$id] = $value;
            }
        }
        $this->enchantmentManager = new EnchantmentManager($this->core);
        $this->scrollManager = new ScrollManager($this->core);
        $this->setManager = new SetManager($this->core);
        $this->addTrinket(new AbsorptionTrinket());
        $this->addTrinket(new GrapplingHookTrinket());
        $this->addTrinket(new HealingTrinket());
        $this->addTrinket(new ResistanceTrinket());
        $this->addMask(new GuardMask());
        $this->addMask(new PilgrimMask());
        $this->addMask(new PrisonerMask());
        $this->addMask(new BuffMask());
        $this->addMask(new CactusMask());
        $this->addMask(new CupidMask());
        $this->addMask(new TinkererMask());
        $this->addMask(new ShadowMask());
        $this->addMask(new JailorMask());
        $this->addMask(new FocusMask());
        $this->addMask(new FireflyMask());
        $this->addPickaxePrestige(new ClueScrollMastery());
        $this->addPickaxePrestige(new EnergyMastery());
        $this->addPickaxePrestige(new ForgeMaster());
        $this->addPickaxePrestige(new Grinder());
        $this->addPickaxePrestige(new Hoarder());
        $this->addPickaxePrestige(new Inquisitive());
        $this->addPickaxePrestige(new MeteoriteMastery());
        $this->addPickaxePrestige(new OreExtractor());
        $this->addPickaxePrestige(new ShardMastery());
        $this->addPickaxePrestige(new XPMastery());
        self::registerItem(Absorber::class);
        self::registerItem(AethicCrate::class);
        self::registerItem(BlackScroll::class);
        self::registerItem(ChargeOrb::class);
        self::registerItem(ChargeOrbSlot::class);
        self::registerItem(ClueScroll::class);
        self::registerItem(ClueScrollCasket::class);
        self::registerItem(Contraband::class);
        self::registerItem(EnchantmentBook::class);
        self::registerItem(EnchantmentDust::class);
        self::registerItem(EnchantmentOrb::class);
        self::registerItem(EnchantmentPage::class);
        self::registerItem(EnchantmentReroll::class);
        self::registerItem(Energy::class);
        self::registerItem(EnergyBooster::class);
        self::registerItem(ExecutiveBooster::class);
        self::registerItem(ForgeFuel::class);
        self::registerItem(GKitBeacon::class);
        self::registerItem(GKitFlare::class);
        self::registerItem(GKitItemGenerator::class);
        self::registerItem(OreGenBooster::class);
        self::registerItem(HeroicToken::class);
        self::registerItem(HomeExpansion::class);
        self::registerItem(ItemNameTag::class);
        self::registerItem(Lootbox::class);
        self::registerItem(types\custom\Mask::class);
        self::registerItem(MeteorFlare::class);
        self::registerItem(MoneyNote::class);
        self::registerItem(MultiMask::class);
        self::registerItem(MysteryClueScroll::class);
        self::registerItem(MysteryEnchantmentBook::class);
        self::registerItem(MysteryEnchantmentOrb::class);
        self::registerItem(MysteryTrinketBox::class);
        self::registerItem(OreGenBooster::class);
        self::registerItem(OreGenerator::class);
        self::registerItem(PrestigeToken::class);
        self::registerItem(RandomGKit::class);
        self::registerItem(RankNote::class);
        self::registerItem(Satchel::class);
        self::registerItem(Shard::class);
        self::registerItem(ShowcaseExpansion::class);
        self::registerItem(SlaughterLootbag::class);
        self::registerItem(SpaceVisor::class);
        self::registerItem(Title::class);
        self::registerItem(Token::class);
        self::registerItem(Trinket::class);
        self::registerItem(VaultExpansion::class);
        self::registerItem(WhiteScroll::class);
        self::registerItem(XPBooster::class);
        self::registerItem(XPBottle::class);
        self::registerItem(SlotBotTicket::class);
        self::registerItem(KeyComponentB::class);
        self::registerItem(KeyComponentA::class);
        self::registerItem(InterdimensionalKey::class);
        $this->registerItem(HadesLootbag::class);
        $this->registerItem(KOTHLootbag::class);
        EnchantmentIdMap::getInstance()->register(50, (new Enchantment("Custom", \pocketmine\item\enchantment\Rarity::COMMON, 0, 0, 1)));
        ItemFactory::getInstance()->register(new Bow(new ItemIdentifier(ItemIds::BOW, 0), "Bow"), true);
        ItemFactory::getInstance()->register(new Fireworks(new ItemIdentifier(ItemIds::FIREWORKS, 0), "Fireworks"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::CHAIN_HELMET, 166, 2, Armor::TIER_CHAIN, Armor::SLOT_HEAD, 11, "Chain Helmet"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::CHAIN_CHESTPLATE, 241, 5, Armor::TIER_CHAIN, Armor::SLOT_CHESTPLATE, 11, "Chain Chestplate"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::CHAIN_LEGGINGS, 226, 3, Armor::TIER_CHAIN, Armor::SLOT_LEGGINGS, 11, "Chain Leggings"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::CHAIN_BOOTS, 196, 1, Armor::TIER_CHAIN, Armor::SLOT_BOOTS, 11, "Chain Boots"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::GOLD_HELMET, 78, 2, Armor::TIER_GOLD, Armor::SLOT_HEAD, 21, "Gold Helmet"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::GOLD_CHESTPLATE, 113, 5, Armor::TIER_GOLD, Armor::SLOT_CHESTPLATE, 21, "Gold Chestplate"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::GOLD_LEGGINGS, 106, 4, Armor::TIER_GOLD, Armor::SLOT_LEGGINGS, 21, "Gold Leggings"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::GOLD_BOOTS, 92, 1, Armor::TIER_GOLD, Armor::SLOT_BOOTS, 21, "Gold Boots"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::IRON_HELMET, 166, 2, Armor::TIER_IRON, Armor::SLOT_HEAD, 31, "Iron Helmet"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::IRON_CHESTPLATE, 241, 6, Armor::TIER_IRON, Armor::SLOT_CHESTPLATE, 31, "Iron Chestplate"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::IRON_LEGGINGS, 226, 5, Armor::TIER_IRON, Armor::SLOT_LEGGINGS, 31, "Iron Leggings"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::IRON_BOOTS, 196, 2, Armor::TIER_IRON, Armor::SLOT_BOOTS, 31, "Iron Boots"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::DIAMOND_HELMET, 364, 3, Armor::TIER_DIAMOND, Armor::SLOT_HEAD, 41, "Diamond Helmet"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::DIAMOND_CHESTPLATE, 529, 8, Armor::TIER_DIAMOND, Armor::SLOT_CHESTPLATE, 41, "Diamond Chestplate"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::DIAMOND_LEGGINGS, 496, 6, Armor::TIER_DIAMOND, Armor::SLOT_LEGGINGS, 41, "Diamond Leggings"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::DIAMOND_BOOTS, 430, 3, Armor::TIER_DIAMOND, Armor::SLOT_BOOTS, 41, "Diamond Boots"), true);
        ItemFactory::getInstance()->register(new Sword(ItemIds::WOODEN_SWORD, 11, ToolTier::WOOD(), "Wooden Sword"), true);
        ItemFactory::getInstance()->register(new Sword(ItemIds::STONE_SWORD, 16, ToolTier::STONE(), "Stone Sword"), true);
        ItemFactory::getInstance()->register(new Sword(ItemIds::GOLD_SWORD, 21, ToolTier::GOLD(), "Gold Sword"), true);
        ItemFactory::getInstance()->register(new Sword(ItemIds::IRON_SWORD, 31, ToolTier::IRON(), "Iron Sword"), true);
        ItemFactory::getInstance()->register(new Sword(ItemIds::DIAMOND_SWORD, 41, ToolTier::DIAMOND(), "Diamond Sword"), true);
        ItemFactory::getInstance()->register(new Axe(ItemIds::WOODEN_AXE, 11, ToolTier::WOOD(), "Wooden Axe"), true);
        ItemFactory::getInstance()->register(new Axe(ItemIds::STONE_AXE, 16, ToolTier::STONE(), "Stone Axe"), true);
        ItemFactory::getInstance()->register(new Axe(ItemIds::GOLD_AXE, 21, ToolTier::GOLD(), "Gold Axe"), true);
        ItemFactory::getInstance()->register(new Axe(ItemIds::IRON_AXE, 31, ToolTier::IRON(), "Iron Axe"), true);
        ItemFactory::getInstance()->register(new Axe(ItemIds::DIAMOND_AXE, 41, ToolTier::DIAMOND(), "Diamond Axe"), true);
        ItemFactory::getInstance()->register(new Pickaxe(ItemIds::WOODEN_PICKAXE, 0, "Wooden Pickaxe", ToolTier::WOOD()), true);
        ItemFactory::getInstance()->register(new Pickaxe(ItemIds::STONE_PICKAXE, 0, "Stone Pickaxe", ToolTier::STONE()), true);
        ItemFactory::getInstance()->register(new Pickaxe(ItemIds::GOLD_PICKAXE, 0, "Gold Pickaxe", ToolTier::GOLD()), true);
        ItemFactory::getInstance()->register(new Pickaxe(ItemIds::IRON_PICKAXE, 0, "Iron Pickaxe", ToolTier::IRON()), true);
        ItemFactory::getInstance()->register(new Pickaxe(ItemIds::DIAMOND_PICKAXE, 0, "Diamond Pickaxe", ToolTier::DIAMOND()), true);
        ItemFactory::getInstance()->register(new class(new ItemIdentifier(ItemIds::MINECART, 0), "Minecart") extends Minecart {

            /**
             * @return int
             */
            public function getMaxStackSize(): int {
                return 64;
            }
        }, true);
        EntityFactory::getInstance()->register(AbsorptionPotion::class, function(World $world, CompoundTag $nbt): AbsorptionPotion {
            return new AbsorptionPotion(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        },  ['AbsorptionPotion']);
        EntityFactory::getInstance()->register(GrapplingHook::class, function(World $world, CompoundTag $nbt): GrapplingHook {
            return new GrapplingHook(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        },  ['GrapplingHook']);
        EntityFactory::getInstance()->register(ResistancePotion::class, function(World $world, CompoundTag $nbt): ResistancePotion {
            return new ResistancePotion(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        },  ['ResistancePotion']);

        $this->initCustomies();
    }

    private function initCustomies() {
        CustomiesItemFactory::getInstance()->registerItem(ItemSkinScroll::class, "nexus:skin_scroll", "Item Skin Scroll");
        self::registerItem(SkinScroll::class);

        foreach (LevelManager::getSetup()->getNested("item-skin.pickaxe") as ["id" => $id, "name" => $name, "rarity" => $rarity]) {
            $this->registerItemSkin($id, $name, BlockToolType::PICKAXE, $rarity);
        }
        foreach (LevelManager::getSetup()->getNested("item-skin.sword") as ["id" => $id, "name" => $name, "rarity" => $rarity]) {
            $this->registerItemSkin($id, $name, BlockToolType::SWORD, $rarity);
        }
    }

    /**
     * @param string $identifier ("amethyst_pickaxe")
     * @param string $name ("Amethyst Pickaxe")
     * @param int $type (BlockToolType::PICKAXE)
     */
    private function registerItemSkin(string $identifier, string $name, int $type, string $rarity = Rarity::SIMPLE) {
        // $name -> "Amethyst Pickaxe" -> "amethyst_pickaxe"
        switch($type) {
            case BlockToolType::PICKAXE:
                self::$registeredSkins[$identifier] = [$name, BlockToolType::PICKAXE, $rarity];

                CustomiesItemFactory::getInstance()->registerItem(WoodenSkinnedPickaxe::class, "nexus:$identifier" . "_wood", $name);
                $id = CustomiesItemFactory::getInstance()->get("nexus:$identifier" . "_wood")->getId();
                self::$animationIDs[$id] = $identifier;

                CustomiesItemFactory::getInstance()->registerItem(StoneSkinnedPickaxe::class, "nexus:$identifier" . "_stone", $name);
                $id = CustomiesItemFactory::getInstance()->get("nexus:$identifier" . "_stone")->getId();
                self::$animationIDs[$id] = $identifier;

                CustomiesItemFactory::getInstance()->registerItem(GoldSkinnedPickaxe::class, "nexus:$identifier" . "_gold", $name);
                $id = CustomiesItemFactory::getInstance()->get("nexus:$identifier" . "_gold")->getId();
                self::$animationIDs[$id] = $identifier;

                CustomiesItemFactory::getInstance()->registerItem(IronSkinnedPickaxe::class, "nexus:$identifier" . "_iron", $name);
                $id = CustomiesItemFactory::getInstance()->get("nexus:$identifier" . "_iron")->getId();
                self::$animationIDs[$id] = $identifier;

                CustomiesItemFactory::getInstance()->registerItem(DiamondSkinnedPickaxe::class, "nexus:$identifier" . "_diamond", $name);
                $id = CustomiesItemFactory::getInstance()->get("nexus:$identifier" . "_diamond")->getId();
                self::$animationIDs[$id] = $identifier;
                break;
            case BlockToolType::SWORD:
                self::$registeredSkins[$identifier] = [$name, BlockToolType::SWORD, $rarity];

                CustomiesItemFactory::getInstance()->registerItem(WoodenSkinnedSword::class, "nexus:$identifier" . "_wood", $name);
                $id = CustomiesItemFactory::getInstance()->get("nexus:$identifier" . "_wood")->getId();
                self::$animationIDs[$id] = $identifier;

                CustomiesItemFactory::getInstance()->registerItem(StoneSkinnedSword::class, "nexus:$identifier" . "_stone", $name);
                $id = CustomiesItemFactory::getInstance()->get("nexus:$identifier" . "_stone")->getId();
                self::$animationIDs[$id] = $identifier;

                CustomiesItemFactory::getInstance()->registerItem(GoldSkinnedSword::class, "nexus:$identifier" . "_gold", $name);
                $id = CustomiesItemFactory::getInstance()->get("nexus:$identifier" . "_gold")->getId();
                self::$animationIDs[$id] = $identifier;

                CustomiesItemFactory::getInstance()->registerItem(IronSkinnedSword::class, "nexus:$identifier" . "_iron", $name);
                $id = CustomiesItemFactory::getInstance()->get("nexus:$identifier" . "_iron")->getId();
                self::$animationIDs[$id] = $identifier;

                CustomiesItemFactory::getInstance()->registerItem(DiamondSkinnedSword::class, "nexus:$identifier" . "_diamond", $name);
                $id = CustomiesItemFactory::getInstance()->get("nexus:$identifier" . "_diamond")->getId();
                self::$animationIDs[$id] = $identifier;
                break;
        }

        // TODO: All other types & subtypes
    }

    public static function getIdentifier(int $id): ?string {
        return self::$animationIDs[$id] ?? null;
    }

    public static function getAnimationIDs() {
        return self::$animationIDs;
    }

    private static array $animationIDs = [];

    private static array $registeredSkins = [];

    /**
     * @param string $identifier
     * @return SkinScroll|null
     */
    public static function getSkinScroll(string $identifier) : ?SkinScroll {
        if(isset(self::$registeredSkins[$identifier])) {
            $name = self::$registeredSkins[$identifier][0];
            $type = self::$registeredSkins[$identifier][1];
            $rarity = self::$registeredSkins[$identifier][2];
            return new SkinScroll($identifier, $type, $name, $rarity);
        }
        return null;
    }

    public static function getItemSkins(string $type) : array {
        $return = [];
        if($type === "sword") {
            foreach (self::$registeredSkins as $identifier => [$name, $type2, $rarity]) {
                if($type2 === BlockToolType::SWORD) {
                    $return[] = [$identifier, $name, $type2, $rarity];
                }
            }
        }
        if($type === "pickaxe") {
            foreach (self::$registeredSkins as $identifier => [$name, $type2, $rarity]) {
                if($type2 === BlockToolType::PICKAXE) {
                    $return[] = [$identifier, $name, $type2, $rarity];
                }
            }
        }
        return $return;
    }

    /**
     * @param Prestige $prestige
     */
    public function addPickaxePrestige(Prestige $prestige): void {
        $this->pickaxePrestiges[$prestige->getIdentifier()] = $prestige;
    }

    /**
     * @return Prestige[]
     */
    public function getPickaxePrestiges(): array {
        return $this->pickaxePrestiges;
    }

    /**
     * @param string $id
     *
     * @return Prestige
     */
    public function getPickaxePrestige(string $id): Prestige {
        return $this->pickaxePrestiges[$id];
    }

    /**
     * @param \core\game\item\trinket\Trinket $trinket
     */
    public function addTrinket(\core\game\item\trinket\Trinket $trinket): void {
        $this->trinkets[$trinket->getName()] = $trinket;
    }

    /**
     * @return \core\game\item\trinket\Trinket[]
     */
    public function getTrinkets(): array {
        return $this->trinkets;
    }

    /**
     * @param string $name
     *
     * @return \core\game\item\trinket\Trinket
     */
    public function getTrinket(string $name): \core\game\item\trinket\Trinket {
        return $this->trinkets[$name];
    }

    /**
     * @param Mask $mask
     */
    public function addMask(Mask $mask): void {
        $this->masks[$mask->getName()] = $mask;
    }

    /**
     * @return Mask[]
     */
    public function getMasks(): array {
        return $this->masks;
    }

    /**
     * @param string $name
     *
     * @return Mask
     */
    public function getMask(string $name): Mask {
        return $this->masks[$name];
    }

    public function saveRedeemed(): void {
        $config = new Config($this->core->getDataFolder() . "redeemed.json", Config::JSON);
        $config->setAll($this->redeemed);
        $config->save();
    }

    /**
     * @return EnchantmentManager
     */
    public function getEnchantmentManager(): EnchantmentManager {
        return $this->enchantmentManager;
    }

    /**
     * @return ScrollManager
     */
    public function getScrollManager(): ScrollManager {
        return $this->scrollManager;
    }

    /**
     * @return SetManager
     */
    public function getSetManager() : SetManager
    {
        return $this->setManager;
    }

    /**
     * @param NexusPlayer $player
     * @param Item $tool
     *
     * @return bool
     * @throws LevelException
     */
    public static function canUseTool(NexusPlayer $player, Item $tool): bool {
        if(!$player->isLoaded()) {
            return true;
        }
        $level = XPUtils::xpToLevel($player->getDataSession()->getXP());
        return $level >= self::getLevelToUseTool($tool);
    }

    /**
     * @param Item $tool
     *
     * @return int
     * @throws LevelException
     */
    public static function getLevelToUseTool(Item $tool): int {
        if((!$tool instanceof TieredTool) and (!$tool instanceof Enchantable) or $tool instanceof Bow) {
            return 0;
        }
        $tier = $tool instanceof TieredTool ? $tool->getTier()->id() : $tool->getTierId();
        switch($tier) {
            case ToolTier::WOOD()->id():
                return 0;
                break;
            case Armor::TIER_CHAIN;
            case ToolTier::STONE()->id():
                if($tool instanceof Sword or $tool instanceof Armor or $tool instanceof Axe) {
                    return 10;
                }
                return 30;
                break;
            case Armor::TIER_GOLD:
            case ToolTier::GOLD()->id():
                if($tool instanceof Sword or $tool instanceof Armor or $tool instanceof Axe) {
                    return 30;
                }
                return 50;
                break;
            case Armor::TIER_IRON:
            case ToolTier::IRON()->id():
                if($tool instanceof Sword or $tool instanceof Armor or $tool instanceof Axe) {
                    return 60;
                }
                return 70;
                break;
            case Armor::TIER_LEATHER:
            case Armor::TIER_DIAMOND:
            case ToolTier::DIAMOND()->id():
                if($tool instanceof Sword or $tool instanceof Armor or $tool instanceof Axe or $tool->getNamedTag()->getString("set", "") !== "") {
                    return 100;
                }
                return 90;
                break;
            default:
                throw new LevelException("Invalid tool tier: " . $tier);
        }
    }

    /**
     * @param string $rarity
     *
     * @return int
     */
    public static function getLevelToUseRarity(string $rarity): int {
        switch($rarity) {
            case Rarity::SIMPLE:
                return 0;
                break;
            case Rarity::UNCOMMON:
                return 30;
                break;
            case Rarity::ELITE:
                return 50;
                break;
            case Rarity::ULTIMATE:
                return 70;
                break;
            case Rarity::LEGENDARY:
                return 90;
                break;
            case Rarity::GODLY:
                return 100;
                break;
            default:
                return 100;
        }
    }

    /**
     * @param string $rarity
     *
     * @return ToolTier
     */
    public static function getToolTierByRarity(string $rarity): ToolTier {
        switch($rarity) {
            case Rarity::SIMPLE:
                $tier = ToolTier::WOOD();
                break;
            case Rarity::UNCOMMON:
                $tier = ToolTier::STONE();
                break;
            case Rarity::ELITE:
                $tier = ToolTier::GOLD();
                break;
            case Rarity::ULTIMATE:
                $tier = ToolTier::IRON();
                break;
            default:
                $tier = ToolTier::DIAMOND();
                break;
        }
        return $tier;
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return bool
     */
    public static function canPrestige(Pickaxe $pickaxe): bool {
        if($pickaxe->getPrestige() >= 10) {
            return false;
        }
        self::getPrestigeRequirements($pickaxe->getPrestige(), $pickaxe->getBlockToolHarvestLevel(), $blocks, $levels);
        if($blocks === 0 and $levels === 0) {
            return false;
        }
        $level = XPUtils::xpToLevel($pickaxe->getEnergy(), RPGManager::ENERGY_MODIFIER);
        if($pickaxe->getBlocks() >= $blocks and $level >= $levels) {
            return true;
        }
        return false;
    }

    /**
     * @param int $prestige
     * @param int $harvestLevel
     * @param int $blocks
     * @param int $levels
     */
    public static function getPrestigeRequirements(int $prestige, int $harvestLevel, &$blocks ,&$levels): void {
        $blocks = 0;
        $levels = 0;
        switcH($prestige + 1) {
            case 1:
                $blocks = 7000 + (500 * $harvestLevel);
                $levels = 15 + (5 * $harvestLevel);
                break;
            case 2:
                $blocks = 15000 + (1000 * $harvestLevel);
                $levels = 20 + (5 * $harvestLevel);
                break;
            case 3:
                $blocks = 22500 + (2500 * $harvestLevel);
                $levels = 25 + (5 * $harvestLevel);
                break;
            case 4:
                $blocks = 31000 + (4000 * $harvestLevel);
                $levels = 30 + (5 * $harvestLevel);
                break;
            case 5:
                $blocks = 40000 + (5000 * $harvestLevel);
                $levels = 35 + (5 * $harvestLevel);
                break;
            case 6:
                $blocks = 54000 + (6000 * $harvestLevel);
                $levels = 45 + (5 * $harvestLevel);
                break;
            case 7:
                $blocks = 66500 + (7000 * $harvestLevel);
                $levels = 55 + (5 * $harvestLevel);
                break;
            case 8:
                $blocks = 82000 + (8000 * $harvestLevel);
                $levels = 65 + (5 * $harvestLevel);
                break;
            case 9:
                $blocks = 100000 + (9000 * $harvestLevel);
                $levels = 75 + (5 * $harvestLevel);
                break;
            case 10:
                $blocks = 110000 + (15000 * $harvestLevel);
                $levels = 85 + (5 * $harvestLevel);
                break;
        }
    }

    /**
     * @param Block $ore
     *
     * @return int
     */
    public static function getLevelToMineOre(Block $ore): int {
        switch($ore->getId()) {
            case BlockLegacyIds::COAL_ORE:
                return 0;
                break;
            case BlockLegacyIds::IRON_ORE:
                return 10;
                break;
            case BlockLegacyIds::LAPIS_ORE:
                return 30;
                break;
            case BlockLegacyIds::GLOWING_REDSTONE_ORE:
            case BlockLegacyIds::REDSTONE_ORE:
                return 50;
                break;
            case BlockLegacyIds::GOLD_ORE:
                return 70;
                break;
            case BlockLegacyIds::DIAMOND_ORE:
                return 90;
                break;
            case BlockLegacyIds::EMERALD_ORE:
                return 100;
                break;
            default:
                return 0;
        }
    }

    /**
     * @param Block $ore
     *
     * @return string
     */
    public static function getColorByOre(Block $ore): string {
        switch($ore->getId()) {
            case BlockLegacyIds::COAL_ORE:
                return TextFormat::DARK_GRAY;
                break;
            case BlockLegacyIds::IRON_ORE:
                return TextFormat::GRAY;
                break;
            case BlockLegacyIds::LAPIS_ORE:
                return TextFormat::DARK_BLUE;
                break;
            case BlockLegacyIds::REDSTONE_ORE:
                return TextFormat::RED;
                break;
            case BlockLegacyIds::GOLD_ORE:
                return TextFormat::YELLOW;
                break;
            case BlockLegacyIds::DIAMOND_ORE:
                return TextFormat::AQUA;
                break;
            case BlockLegacyIds::EMERALD_ORE:
                return TextFormat::GREEN;
                break;
            default:
                return TextFormat::WHITE;
        }
    }

    /**
     * @param int $level
     *
     * @return Block
     */
    public static function getOreByLevel(int $level): Block {
        if($level >= 100) {
            $block = BlockFactory::getInstance()->get(BlockLegacyIds::EMERALD_ORE, 0);
        }
        elseif($level >= 90) {
            $block = BlockFactory::getInstance()->get(BlockLegacyIds::DIAMOND_ORE, 0);
        }
        elseif($level >= 70) {
            $block = BlockFactory::getInstance()->get(BlockLegacyIds::GOLD_ORE, 0);
        }
        elseif($level >= 50) {
            $block = BlockFactory::getInstance()->get(BlockLegacyIds::REDSTONE_ORE, 0);
        }
        elseif($level >= 30) {
            $block = BlockFactory::getInstance()->get(BlockLegacyIds::LAPIS_ORE, 0);
        }
        elseif($level >= 10) {
            $block = BlockFactory::getInstance()->get(BlockLegacyIds::IRON_ORE, 0);
        }
        else {
            $block = BlockFactory::getInstance()->get(BlockLegacyIds::COAL_ORE, 0);
        }
        return $block;
    }

    /**
     * @param int $level
     *
     * @return string
     */
    public static function getRarityByLevel(int $level): string {
        if($level < 10) {
            $rarity = Rarity::SIMPLE;
        }
        elseif($level < 30) {
            $rarity = Rarity::UNCOMMON;
        }
        elseif($level < 50) {
            $rarity = Rarity::ELITE;
        }
        elseif($level < 70) {
            $rarity = Rarity::ULTIMATE;
        }
        elseif($level < 90) {
            $rarity = Rarity::LEGENDARY;
        }
        else {
            $rarity = Rarity::GODLY;
        }
        return $rarity;
    }

    /**
     * @param int $level
     *
     * @return string
     */
    public static function getRarityForXPByLevel(int $level): string {
        if($level < 30) {
            $rarity = Rarity::SIMPLE;
        }
        elseif($level < 50) {
            $rarity = Rarity::UNCOMMON;
        }
        elseif($level < 70) {
            $rarity = Rarity::ELITE;
        }
        elseif($level < 90) {
            $rarity = Rarity::ULTIMATE;
        }
        elseif($level < 100) {
            $rarity = Rarity::LEGENDARY;
        }
        else {
            $rarity = Rarity::GODLY;
        }
        return $rarity;
    }

    /**
     * @param Block $block
     *
     * @return Item
     */
    public static function getRefinedDrop(Block $block): Item {
        switch($block->getId()) {
            case BlockLegacyIds::EMERALD_ORE:
                return ItemFactory::getInstance()->get(ItemIds::EMERALD);
                break;
            case BlockLegacyIds::DIAMOND_ORE:
                return ItemFactory::getInstance()->get(ItemIds::DIAMOND);
                break;
            case BlockLegacyIds::GOLD_ORE:
                return ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT);
                break;
            case BlockLegacyIds::REDSTONE_ORE:
                return ItemFactory::getInstance()->get(ItemIds::REDSTONE);
                break;
            case BlockLegacyIds::LAPIS_ORE:
                return ItemFactory::getInstance()->get(ItemIds::DYE, 4);
                break;
            case BlockLegacyIds::IRON_ORE:
                return ItemFactory::getInstance()->get(ItemIds::IRON_INGOT);
                break;
            case BlockLegacyIds::COAL_ORE:
                return ItemFactory::getInstance()->get(ItemIds::COAL);
                break;
            case BlockLegacyIds::PRISMARINE:
                return ItemFactory::getInstance()->get(ItemIds::PRISMARINE);
                break;
        }
        return ItemFactory::getInstance()->get(ItemIds::AIR);
    }


    /**
     * @param string $item
     */
    public function registerItem(string $item): void {
        $this->items[$item] = $item;
    }

    /**
     * @param Item $item
     *
     * @return CustomItem|null
     */
    public function getItem(Item $item): ?CustomItem {
        foreach($this->items as $class) {
            if(call_user_func($class . "::isInstanceOf", $item)) {
                return call_user_func($class . "::fromItem", $item);
            }
        }
        return null;
    }

    /**
     * @param string $id
     */
    public function setRedeemed(string $id): void {
        $this->redeemed[$id] = time();
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function isRedeemed(string $id): bool {
        return isset($this->redeemed[$id]);
    }
}