<?php
declare(strict_types=1);

namespace core\display\animation;

use core\display\animation\entity\CrateEntity;
use core\display\animation\entity\CrystalEntity;
use core\display\animation\entity\MeteorBaseEntity;
use core\display\animation\entity\TorchEntity;
use core\display\animation\entity\WormholeSelectEntity;
use core\display\animation\entity\ItemBaseEntity;
use core\display\animation\entity\LevelUpEntity;
use core\display\animation\task\AnimationHeartbeatTask;
use core\game\item\ItemManager;
use core\Nexus;
use libs\utils\Utils;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Skin;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class AnimationManager {

    const METEOR_ID = -1;
    const FLARE_ID = -2;
    const SPACE_CHEST_ID = -3;
    const CRYSTAL_ID = -4;

    /** @var Nexus */
    private $core;

    /** @var Animation[] */
    private $animations = [];

    /** @var Skin[] */
    private $skins = [];

    /**
     * AnimationManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getScheduler()->scheduleRepeatingTask(new AnimationHeartbeatTask($this), 1);
        $this->init();
    }

    public function init(): void {
        EntityFactory::getInstance()->register(ItemBaseEntity::class, function(World $world, CompoundTag $nbt): ItemBaseEntity {
            return new ItemBaseEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        },  ['ItemBaseEntity']);
        EntityFactory::getInstance()->register(LevelUpEntity::class, function(World $world, CompoundTag $nbt): LevelUpEntity {
            return new LevelUpEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        },  ['LevelUpEntity']);
        EntityFactory::getInstance()->register(WormholeSelectEntity::class, function(World $world, CompoundTag $nbt): WormholeSelectEntity {
            return new WormholeSelectEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        },  ['WormholeSelectEntity']);
        EntityFactory::getInstance()->register(TorchEntity::class, function(World $world, CompoundTag $nbt): TorchEntity {
            return new TorchEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        },  ['TorchEntity']);
        EntityFactory::getInstance()->register(MeteorBaseEntity::class, function(World $world, CompoundTag $nbt): MeteorBaseEntity {
            return new MeteorBaseEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        },  ['MeteorBaseEntity']);
        EntityFactory::getInstance()->register(CrateEntity::class, function(World $world, CompoundTag $nbt): CrateEntity {
            return new CrateEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        },  ['CrateEntity']);
        EntityFactory::getInstance()->register(CrystalEntity::class, function(World $world, CompoundTag $nbt): CrystalEntity {
            return new CrystalEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        },  ['CrystalEntity']);
        $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "geometry" . DIRECTORY_SEPARATOR;
        $geometryData = file_get_contents($path . "item.json");
        $this->skins[ItemIds::REDSTONE_TORCH] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "redstone_torch.png"), "redstone_torch", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::COAL] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "coal.png"), "coal", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::IRON_INGOT] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "iron_ingot.png"), "iron_ingot", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DYE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "lapis_lazuli.png"), "lapis_lazuli", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::REDSTONE_DUST] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "redstone.png"), "redstone", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::GOLD_INGOT] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "gold_ingot.png"), "gold_ingot", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DIAMOND] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond.png"), "diamond", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::EMERALD] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "emerald.png"), "emerald", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::ENCHANTED_BOOK] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "enchanted_book.png"), "enchanted_book", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::PAPER] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "paper.png"), "paper", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DYE . ":8"] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "gray_dye.png"), "gray_dye", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DYE . ":10"] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "lime_dye.png"), "lime_dye", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DYE . ":6"] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "cyan_dye.png"), "cyan_dye", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DYE . ":11"] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "dandelion_yellow.png"), "dandelion_yellow", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DYE . ":14"] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "orange_dye.png"), "orange_dye", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DYE . ":1"] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "red_dye.png"), "red_dye", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DYE . ":13"] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "magenta_dye.png"), "magenta_dye", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::CHAIN_HELMET] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "chainmail_helmet.png"), "chainmail_helmet", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::CHAIN_CHESTPLATE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "chainmail_chestplate.png"), "chainmail_chestplate", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::CHAIN_LEGGINGS] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "chainmail_leggings.png"), "chainmail_leggings", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::CHAIN_BOOTS] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "chainmail_boots.png"), "chainmail_boots", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::GOLD_HELMET] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "golden_helmet.png"), "golden_helmet", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::GOLD_CHESTPLATE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "golden_chestplate.png"), "golden_chestplate", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::GOLD_LEGGINGS] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "golden_leggings.png"), "golden_leggings", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::GOLD_BOOTS] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "golden_boots.png"), "golden_boots", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::IRON_HELMET] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "iron_helmet.png"), "iron_helmet", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::IRON_CHESTPLATE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "iron_chestplate.png"), "iron_chestplate", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::IRON_LEGGINGS] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "iron_leggings.png"), "iron_leggings", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::IRON_BOOTS] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "iron_boots.png"), "iron_boots", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DIAMOND_HELMET] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_helmet.png"), "diamond_helmet", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DIAMOND_CHESTPLATE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_chestplate.png"), "diamond_chestplate", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DIAMOND_LEGGINGS] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_leggings.png"), "diamond_leggings", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::DIAMOND_BOOTS] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_boots.png"), "diamond_boots", "", "geometry.item.default", $geometryData);
        
        $this->skins[ItemIds::LEATHER_HELMET] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_helmet.png"), "diamond_helmet", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::LEATHER_CHESTPLATE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_chestplate.png"), "diamond_chestplate", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::LEATHER_LEGGINGS] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_leggings.png"), "diamond_leggings", "", "geometry.item.default", $geometryData);
        $this->skins[ItemIds::LEATHER_BOOTS] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_boots.png"), "diamond_boots", "", "geometry.item.default", $geometryData);
        
        $geometryData = file_get_contents($path . "block.json");
        $this->skins[ItemIds::ENDER_CHEST] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "ender_chest.png"), "ender_chest", "", "geometry.block.default", $geometryData);
        $this->skins[ItemIds::COAL_ORE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "coal_ore.png"), "coal_ore", "", "geometry.block.default", $geometryData);
        $this->skins[ItemIds::IRON_ORE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "iron_ore.png"), "iron_ore", "", "geometry.block.default", $geometryData);
        $this->skins[ItemIds::LAPIS_ORE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "lapis_ore.png"), "lapis_ore", "", "geometry.block.default", $geometryData);
        $this->skins[ItemIds::REDSTONE_ORE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "redstone_ore.png"), "redstone_ore", "", "geometry.block.default", $geometryData);
        $this->skins[ItemIds::GOLD_ORE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "gold_ore.png"), "gold_ore", "", "geometry.block.default", $geometryData);
        $this->skins[ItemIds::DIAMOND_ORE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_ore.png"), "diamond_ore", "", "geometry.block.default", $geometryData);
        $this->skins[ItemIds::EMERALD_ORE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "emerald_ore.png"), "emerald_ore", "", "geometry.block.default", $geometryData);
        $geometryData = file_get_contents($path . "meteor.json");
        $this->skins[self::METEOR_ID] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "meteor.png"), "meteor", "", "geometry.block.default", $geometryData);
        $this->skins[self::FLARE_ID] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "flare.png"), "flare", "", "geometry.block.default", $geometryData);
        $geometryData = file_get_contents($path . "block.json"); // uses space_chest.json
        $this->skins[self::SPACE_CHEST_ID] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "space_chest.png"), "space_chest", "", "geometry.block.default", $geometryData);
        $geometryData = file_get_contents($path . "item.json"); // uses crystal.json, crystal.png
        $this->skins[self::CRYSTAL_ID] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond.png"), "crystal", "", "geometry.item.default", $geometryData); // crystal.default
        $geometryData = file_get_contents($path . "tool.json");
        $this->skins[ItemIds::WOODEN_AXE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "wooden_axe.png"), "wooden_axe", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::WOODEN_PICKAXE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "wooden_pickaxe.png"), "wooden_pickaxe", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::WOODEN_SWORD] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "wooden_sword.png"), "wooden_sword", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::STONE_AXE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "stone_axe.png"), "stone_axe", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::STONE_PICKAXE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "stone_pickaxe.png"), "stone_pickaxe", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::STONE_SWORD] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "stone_sword.png"), "stone_sword", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::GOLDEN_AXE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "golden_axe.png"), "golden_axe", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::GOLD_PICKAXE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "golden_pickaxe.png"), "golden_pickaxe", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::GOLD_SWORD] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "golden_sword.png"), "golden_sword", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::IRON_AXE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "iron_axe.png"), "iron_axe", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::IRON_PICKAXE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "iron_pickaxe.png"), "iron_pickaxe", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::IRON_SWORD] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "iron_sword.png"), "iron_sword", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::DIAMOND_AXE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_axe.png"), "diamond_axe", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::DIAMOND_PICKAXE] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_pickaxe.png"), "diamond_pickaxe", "", "geometry.tool.default", $geometryData);
        $this->skins[ItemIds::DIAMOND_SWORD] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "diamond_sword.png"), "diamond_sword", "", "geometry.tool.default", $geometryData);

//        foreach (ItemManager::getAnimationIDs() as $id => $identifier) {
//            $this->registerSkin($id, $identifier);
//        } // TODO: no longer neeeded for now, quick bug fix?
    }

    public function registerSkin(int $id, string $pngFile) {
        $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "geometry" . DIRECTORY_SEPARATOR;
        $geometryData = file_get_contents($path . "item.json");
        $this->skins[$id] = Utils::createSkin(Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . "$pngFile.png"), $pngFile, "", "geometry.tool.default", $geometryData);
    }

    public function tickAnimations(): void {
        foreach($this->animations as $index => $animation) {
            if($animation->isClosed() or $animation->getOwner()->isClosed()) {
                unset($this->animations[$index]);
                continue;
            }
            $animation->tick();
        }
    }

    /**
     * @return Animation[]
     */
    public function getAnimations(): array {
        return $this->animations;
    }

    /**
     * @param Animation $animation
     */
    public function addAnimation(Animation $animation): void {
        $this->animations[] = $animation;
    }

    /**
     * @param int $id
     * @param int|null $damage
     *
     * @return Skin|null
     */
    public function getSkin(int $id, ?int $damage = null): ?Skin {
        if($damage !== null) {
            $id = $id . ":" . $damage;
        }
        return $this->skins[$id] ?? null;
    }
}