<?php

declare(strict_types = 1);

namespace core\game\economy;

use core\game\item\types\custom\CrudeOre;
use core\game\item\types\custom\OreGenerator;
use core\game\item\types\Rarity;
use core\Nexus;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Carpet;
use pocketmine\block\StainedGlass;
use pocketmine\block\StainedGlassPane;
use pocketmine\block\StainedHardenedClay;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wool;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

class EconomyManager {

    /** @var Nexus */
    private $core;

    /** @var EconomyCategory[] */
    private $places = [];

    /** @var PriceEntry[] */
    private $sellables = [];

    /**
     * PriceManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
    }

    public function init() {
        $craftsman = [];
        $blocks = [
            VanillaBlocks::STAINED_GLASS_PANE(),
            VanillaBlocks::GLASS_PANE(),
            VanillaBlocks::STAINED_GLASS(),
            VanillaBlocks::GLASS(),
            VanillaBlocks::STAINED_CLAY(),
            VanillaBlocks::CLAY(),
        ];
        $i = 0;
        foreach($blocks as $block) {
            if($block instanceof StainedGlassPane or $block instanceof StainedGlass or $block instanceof StainedHardenedClay) {
                foreach(DyeColor::getAll() as $color) {
                    $craftsman[] = new PriceEntry($block->setColor($color)->asItem(), $i, $color->name() . " " . $block->getName(), null, 15);
                    ++$i;
                }
                continue;
            }
            $craftsman[] = new PriceEntry($block->asItem(), $i, null, null, 10);
            $i += 2;
        }
        $weaver = [];
        $blocks = [
            VanillaBlocks::WOOL(),
            VanillaBlocks::CARPET()
        ];
        $i = 0;
        foreach($blocks as $block) {
            if($block instanceof Wool or $block instanceof Carpet) {
                foreach(DyeColor::getAll() as $color) {
                    $weaver[] = new PriceEntry($block->setColor($color)->asItem(), $i, $color->name() . " " . $block->getName(), null, 15);
                    ++$i;
                }
                $i += 2;
            }
        }
        $this->places = [
            new EconomyCategory("Ores", ItemFactory::getInstance()->get(ItemIds::COAL_ORE, 0), [
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::COAL_ORE, 0, 1), 0,  null, 0.06),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::COAL, 0, 1), 9, null, 0.32),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::IRON_ORE, 0, 1), 1, null, 0.18),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::IRON_INGOT, 0, 1), 10, null, 0.92),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::LAPIS_ORE, 0, 1), 2, null, 0.45),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::DYE, 4, 1), 11, null, 2.36),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::REDSTONE_ORE, 0, 1), 3, null, 1.36),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::REDSTONE_DUST, 0, 1), 12, null, 7.19),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::GOLD_ORE, 0, 1), 4, null, 4.18),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT, 0, 1), 13, null, 22.12),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::DIAMOND_ORE, 0, 1), 5, null, 7.32),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, 1), 14, null, 38.76),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::EMERALD_ORE, 0, 1), 6, null, 25.53),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::EMERALD, 0, 1), 15, null, 135.26),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::STEAK, 0, 1), 8, null, null, 0.1),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::PRISMARINE, 0, 1), 33, null, 51.06)
            ]),
            new EconomyCategory("Ore Generators", ItemFactory::getInstance()->get(ItemIds::DIAMOND_BLOCK), [
                new PriceEntry((new CrudeOre(Rarity::SIMPLE))->toItem(), 0, null, null, 250, 0),
                new PriceEntry((new CrudeOre(Rarity::UNCOMMON))->toItem(), 1, null, null, 1000, 30),
                new PriceEntry((new CrudeOre(Rarity::ELITE))->toItem(), 2, null, null, 5000, 50),
                new PriceEntry((new CrudeOre(Rarity::ULTIMATE))->toItem(), 3, null, null, 20000, 70),
                new PriceEntry((new CrudeOre(Rarity::LEGENDARY))->toItem(), 4, null, null, 75000, 90),
                new PriceEntry((new CrudeOre(Rarity::GODLY))->toItem(), 5, null, null, 200000, 100),
                new PriceEntry((new OreGenerator(BlockFactory::getInstance()->get(BlockLegacyIds::COAL_ORE, 0)))->toItem(), 9, null, null, 5000, 0),
                new PriceEntry((new OreGenerator(BlockFactory::getInstance()->get(BlockLegacyIds::IRON_ORE, 0)))->toItem(), 10, null, null, 25000, 10),
                new PriceEntry((new OreGenerator(BlockFactory::getInstance()->get(BlockLegacyIds::LAPIS_ORE, 0)))->toItem(), 11, null, null, 75000, 30),
                new PriceEntry((new OreGenerator(BlockFactory::getInstance()->get(BlockLegacyIds::REDSTONE_ORE, 0)))->toItem(), 12, null, null, 300000, 50),
                new PriceEntry((new OreGenerator(BlockFactory::getInstance()->get(BlockLegacyIds::GOLD_ORE, 0)))->toItem(), 13, null, null, 650000, 70),
                new PriceEntry((new OreGenerator(BlockFactory::getInstance()->get(BlockLegacyIds::DIAMOND_ORE, 0)))->toItem(), 14, null, null, 1200000, 90),
                new PriceEntry((new OreGenerator(BlockFactory::getInstance()->get(BlockLegacyIds::EMERALD_ORE, 0)))->toItem(), 15, null, null, 2250000, 100)
            ]),
            new EconomyCategory("Blacksmith", ItemFactory::getInstance()->get(ItemIds::ANVIL), [
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::WOODEN_SWORD, 0, 1), 1, null, null, 100),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::STONE_SWORD, 0, 1), 2, null, null, 500),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::GOLD_SWORD, 0, 1), 3, null, null, 2000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::IRON_SWORD, 0, 1), 4, null, null, 200000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD, 0, 1), 5, null, null, 2000000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::CHAIN_HELMET, 0, 1), 11, null, null, 100),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::GOLD_HELMET, 0, 1), 12, null, null, 1000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::IRON_HELMET, 0, 1), 13, null, null, 100000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET, 0, 1), 14, null, null, 1000000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::CHAIN_CHESTPLATE, 0, 1), 20, null, null, 100),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::GOLD_CHESTPLATE, 0, 1), 21, null, null, 1000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::IRON_CHESTPLATE, 0, 1), 22, null, null, 100000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE, 0, 1), 23, null, null, 1000000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::CHAIN_LEGGINGS, 0, 1), 29, null, null, 100),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::GOLD_LEGGINGS, 0, 1), 30, null, null, 1000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::IRON_LEGGINGS, 0, 1), 31, null, null, 100000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS, 0, 1), 32, null, null, 1000000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::CHAIN_BOOTS, 0, 1), 38, null, null, 100),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::GOLD_BOOTS, 0, 1), 39, null, null, 1000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::IRON_BOOTS, 0, 1), 40, null, null, 100000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS, 0, 1), 41, null, null, 1000000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::BOW, 0, 1), 19, null, null, 5000),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::ARROW, 0, 1), 28, null, null, 100),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE, 0, 1), 16, null, null, 20),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::STONE_PICKAXE, 0, 1), 25, null, null, 200),
                new PriceEntry(ItemFactory::getInstance()->get(ItemIds::GOLD_PICKAXE, 0, 1), 34, null, null, 2000),
            ]),
            new EconomyCategory("Craftsman", ItemFactory::getInstance()->get(ItemIds::CRAFTING_TABLE), $craftsman),
            new EconomyCategory("Weaver", ItemFactory::getInstance()->get(ItemIds::WOOL), $weaver),
            new EconomyCategory("Carpenter", ItemFactory::getInstance()->get(ItemIds::WOOD), [
                new PriceEntry(VanillaBlocks::BIRCH_LOG()->asItem(), 0, null, null, 5),
                new PriceEntry(VanillaBlocks::ACACIA_LOG()->asItem(), 1, null, null, 5),
                new PriceEntry(VanillaBlocks::OAK_LOG()->asItem(), 2, null, null, 5),
                new PriceEntry(VanillaBlocks::DARK_OAK_LOG()->asItem(), 3, null, null, 5),
                new PriceEntry(VanillaBlocks::JUNGLE_LOG()->asItem(), 4, null, null, 5),
                new PriceEntry(VanillaBlocks::SPRUCE_LOG()->asItem(), 5, null, null, 5),
                new PriceEntry(VanillaBlocks::BIRCH_SLAB()->asItem(), 6, null, null, 1),
                new PriceEntry(VanillaBlocks::ACACIA_SLAB()->asItem(), 7, null, null, 1),
                new PriceEntry(VanillaBlocks::OAK_SLAB()->asItem(), 8, null, null, 1),
                new PriceEntry(VanillaBlocks::DARK_OAK_SLAB()->asItem(), 9, null, null, 1),
                new PriceEntry(VanillaBlocks::JUNGLE_SLAB()->asItem(), 10, null, null, 1),
                new PriceEntry(VanillaBlocks::SPRUCE_SLAB()->asItem(), 11, null, null, 1),
                new PriceEntry(VanillaBlocks::BIRCH_STAIRS()->asItem(), 12, null, null, 1),
                new PriceEntry(VanillaBlocks::ACACIA_STAIRS()->asItem(), 13, null, null, 1),
                new PriceEntry(VanillaBlocks::OAK_STAIRS()->asItem(), 14, null, null, 1),
                new PriceEntry(VanillaBlocks::DARK_OAK_STAIRS()->asItem(), 15, null, null, 1),
                new PriceEntry(VanillaBlocks::JUNGLE_STAIRS()->asItem(), 16, null, null, 1),
                new PriceEntry(VanillaBlocks::SPRUCE_STAIRS()->asItem(), 17, null, null, 1),
                new PriceEntry(VanillaBlocks::BIRCH_PLANKS()->asItem(), 18, null, null, 1),
                new PriceEntry(VanillaBlocks::ACACIA_PLANKS()->asItem(), 19, null, null, 1),
                new PriceEntry(VanillaBlocks::OAK_PLANKS()->asItem(), 20, null, null, 1),
                new PriceEntry(VanillaBlocks::DARK_OAK_PLANKS()->asItem(), 21, null, null, 1),
                new PriceEntry(VanillaBlocks::JUNGLE_PLANKS()->asItem(), 22, null, null, 1),
                new PriceEntry(VanillaBlocks::SPRUCE_PLANKS()->asItem(), 23, null, null, 1),
                new PriceEntry(VanillaBlocks::BIRCH_FENCE()->asItem(), 24, null, null, 1),
                new PriceEntry(VanillaBlocks::ACACIA_FENCE()->asItem(), 25, null, null, 1),
                new PriceEntry(VanillaBlocks::OAK_FENCE()->asItem(), 26, null, null, 1),
                new PriceEntry(VanillaBlocks::DARK_OAK_FENCE()->asItem(), 27, null, null, 1),
                new PriceEntry(VanillaBlocks::JUNGLE_FENCE()->asItem(), 28, null, null, 1),
                new PriceEntry(VanillaBlocks::SPRUCE_FENCE()->asItem(), 29, null, null, 1),
                new PriceEntry(VanillaBlocks::BIRCH_DOOR()->asItem(), 30, null, null, 10),
                new PriceEntry(VanillaBlocks::ACACIA_DOOR()->asItem(), 31, null, null, 10),
                new PriceEntry(VanillaBlocks::OAK_DOOR()->asItem(), 32, null, null, 10),
                new PriceEntry(VanillaBlocks::DARK_OAK_DOOR()->asItem(), 33, null, null, 10),
                new PriceEntry(VanillaBlocks::JUNGLE_DOOR()->asItem(), 34, null, null, 10),
                new PriceEntry(VanillaBlocks::SPRUCE_DOOR()->asItem(), 35, null, null, 10),
                new PriceEntry(VanillaBlocks::CHEST()->asItem(), 36, null, null, 100),
                new PriceEntry(VanillaBlocks::OAK_SIGN()->asItem(), 37, null, null, 25),
                new PriceEntry(VanillaBlocks::LADDER()->asItem(), 38, null, null, 50),
                new PriceEntry(VanillaBlocks::SEA_LANTERN()->asItem(), 39, null, null, 100),
                new PriceEntry(VanillaBlocks::GLOWSTONE()->asItem(), 40, null, null, 100),
                new PriceEntry(VanillaBlocks::DIRT()->asItem(), 41, null, null, 5),
                new PriceEntry(VanillaBlocks::GRASS()->asItem(), 42, null, null, 5),
                new PriceEntry(VanillaBlocks::ICE()->asItem(), 43, null, null, 5),
                new PriceEntry(VanillaBlocks::STONE()->asItem(), 44, null, null, 5),
                new PriceEntry(VanillaBlocks::QUARTZ()->asItem(), 45, null, null, 5),
                new PriceEntry(VanillaBlocks::QUARTZ_SLAB()->asItem(), 46, null, null, 5),
                new PriceEntry(VanillaBlocks::ANDESITE()->asItem(), 47, null, null, 5),
                new PriceEntry(VanillaBlocks::POLISHED_ANDESITE()->asItem(), 48, null, null, 5),
                new PriceEntry(VanillaBlocks::DIORITE()->asItem(), 49, null, null, 5),
                new PriceEntry(VanillaBlocks::POLISHED_DIORITE()->asItem(), 50, null, null, 5),
                new PriceEntry(VanillaBlocks::GRANITE()->asItem(), 51, null, null, 5),
                new PriceEntry(VanillaBlocks::POLISHED_GRANITE()->asItem(), 52, null, null, 5),
                new PriceEntry(VanillaBlocks::OAK_FENCE_GATE()->asItem(), 53, null, null, 10)
            ]),
        ];
        foreach($this->getAll() as $entry) {
            if($entry->getSellPrice() !== null) {
                $this->sellables[$entry->getItem()->getId()] = $entry;
            }
        }
    }

    /**
     * @return PriceEntry[]
     */
    public function getAll(): array {
        $all = [];
        foreach($this->places as $place) {
            $all = array_merge($all, $place->getEntries());
        }
        return $all;
    }

    /**
     * @return PriceEntry[]
     */
    public function getSellables(): array {
        return $this->sellables;
    }

    /**
     * @return EconomyCategory[]
     */
    public function getPlaces(): array {
        return $this->places;
    }

    /**
     * @param string $name
     *
     * @return EconomyCategory|null
     */
    public function getPlace(string $name): ?EconomyCategory {
        foreach($this->places as $place) {
            if($place->getName() === $name) {
                return $place;
            }
        }
        return null;
    }
}