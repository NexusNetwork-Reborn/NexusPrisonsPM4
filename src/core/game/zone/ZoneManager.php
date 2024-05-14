<?php
declare(strict_types=1);

namespace core\game\zone;

use core\game\badlands\BadlandsManager;
use core\game\item\types\vanilla\Armor;
use core\Nexus;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ToolTier;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use pocketmine\world\WorldManager;

class ZoneManager {

    /** @var Nexus */
    private $core;

    /** @var Zone[] */
    private $zones = [];

    /** @var BadlandsRegion[] */
    private $badlands = [];

    /** @var Mine[] */
    private $mines = [];

    /**
     * AreaManager constructor.
     *
     * @param Nexus $core
     *
     * @throws ZoneException
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new ZoneListener($core), $core);
        $this->init();
    }

    /**
     * @throws ZoneException
     */
    public function init(): void {
        $config = new Config($this->core->getDataFolder() . "setup.yml", Config::YAML);
        $mines = $config->get("mines");
        $coal = explode(":", $mines["coal"]);
        $iron = explode(":", $mines["iron"]);
        $lapis = explode(":", $mines["lapis"]);
        $redstone = explode(":", $mines["redstone"]);
        $gold = explode(":", $mines["gold"]);
        $diamond = explode(":", $mines["diamond"]);
        $emerald = explode(":", $mines["emerald"]);
        $level = $this->core->getServer()->getWorldManager()->getDefaultWorld();
        $executive = $this->core->getServer()->getWorldManager()->getWorldByName("executive");
        $this->addMine(new Mine("Coal", 0, VanillaBlocks::COAL_ORE()->asItem(), new Position((float)$coal[0], (float)$coal[1], (float)$coal[2], $level)));
        $this->addMine(new Mine("Iron", 10, VanillaBlocks::IRON_ORE()->asItem(), new Position((float)$iron[0], (float)$iron[1], (float)$iron[2], $level)));
        $this->addMine(new Mine("Lapis", 30, VanillaBlocks::LAPIS_LAZULI_ORE()->asItem(), new Position((float)$lapis[0], (float)$lapis[1], (float)$lapis[2], $level)));
        $this->addMine(new Mine("Redstone", 50, VanillaBlocks::REDSTONE_ORE()->asItem(), new Position((float)$redstone[0], (float)$redstone[1], (float)$redstone[2], $level)));
        $this->addMine(new Mine("Gold", 70, VanillaBlocks::GOLD_ORE()->asItem(), new Position((float)$gold[0], (float)$gold[1], (float)$gold[2], $level)));
        $this->addMine(new Mine("Diamond", 90, VanillaBlocks::DIAMOND_ORE()->asItem(), new Position((float)$diamond[0], (float)$diamond[1], (float)$diamond[2], $level)));
        $this->addMine(new Mine("Emerald", 100, VanillaBlocks::EMERALD_ORE()->asItem(), new Position((float)$emerald[0], (float)$emerald[1], (float)$emerald[2], $level)));
        $this->addMine(new Mine("Executive", 100, VanillaBlocks::PRISMARINE()->asItem(), $executive->getSpawnLocation()));

        $zones = $config->get("zones");

        foreach ($zones as $zone) {
            $pos1 = explode(":", $zone["pos1"]);
            $pos2 = explode(":", $zone["pos2"]);
            if(!isset($zone["world"])) {
                $this->addZone(new Zone($zone["tier"], new Position((float)$pos1[0], (float)$pos1[1], (float)$pos1[2], $level), new Position((float)$pos2[0], (float)$pos2[1], (float)$pos2[2], $level)));
            } else {
                $spawn = explode(":", $zone["spawn"]);
                if(!Nexus::getInstance()->getServer()->getWorldManager()->getWorldByName($zone["world"]) !== null) {
                    if(!Nexus::getInstance()->getServer()->getWorldManager()->isWorldLoaded($zone["world"])) Nexus::getInstance()->getServer()->getWorldManager()->loadWorld($zone["world"]);
                    $world = Nexus::getInstance()->getServer()->getWorldManager()->getWorldByName($zone["world"]);
                } else {
                    $world = $level;
                }
                $this->addZone(new Zone($zone["tier"], new Position((float)$pos1[0], (float)$pos1[1], (float)$pos1[2], $world), new Position((float)$pos2[0], (float)$pos2[1], (float)$pos2[2], $world)));
                $this->addBadlands(new BadlandsRegion($zone["tier"], new Position((float)$spawn[0], (float)$spawn[1], (float)$spawn[2], $world), new Position((float)$pos1[0], (float)$pos1[1], (float)$pos1[2], $world), new Position((float)$pos2[0], (float)$pos2[1], (float)$pos2[2], $world), $world));
            }
        }
//        $this->addMine(new Mine("Coal", 0, VanillaBlocks::COAL_ORE()->asItem(), new Position(-242.5, 245, -165.5, $level)));
//        $this->addMine(new Mine("Iron", 10, VanillaBlocks::IRON_ORE()->asItem(), new Position(35.5, 239, -192.5, $level)));
//        $this->addMine(new Mine("Lapis", 30, VanillaBlocks::LAPIS_LAZULI_ORE()->asItem(), new Position(281.5, 228, 117.5, $level)));
//        $this->addMine(new Mine("Redstone", 50, VanillaBlocks::REDSTONE_ORE()->asItem(), new Position(250.5, 232, -63.5, $level)));
//        $this->addMine(new Mine("Gold", 70, VanillaBlocks::GOLD_ORE()->asItem(), new Position(105.5, 235, 88.5, $level)));
//        $this->addMine(new Mine("Diamond", 90, VanillaBlocks::DIAMOND_ORE()->asItem(), new Position(-105.5, 239, 307.5, $level)));
//        $this->addMine(new Mine("Emerald", 100, VanillaBlocks::EMERALD_ORE()->asItem(), new Position(76.5, 249, 280.5, $level)));
//        $this->addZone(new Zone(Armor::TIER_GOLD, new Position(198, 0, -85, $level), new Position(511, 256, 511, $level)));
//        $this->addZone(new Zone(Armor::TIER_GOLD, new Position(-15, 0, -85, $level), new Position(197, 256, 165, $level)));
//        $this->addZone(new Zone(Armor::TIER_DIAMOND, new Position(-15, 0, 166, $level), new Position(197, 256, 511, $level)));
//        $this->addZone(new Zone(Armor::TIER_CHAIN, new Position(-15, 0, -383, $level), new Position(136, 256, -86, $level)));
//        $this->addZone(new Zone(Armor::TIER_CHAIN, new Position(-383, 0, -383, $level), new Position(-16, 256, -2, $level)));
//        $this->addZone(new Zone(Armor::TIER_IRON, new Position(-383, 0, -1, $level), new Position(-16, 256, 511, $level)));
    }

    /**
     * @param Zone $area
     */
    public function addZone(Zone $area): void {
        $this->zones[] = $area;
    }

    /**
     * @param Mine $area
     */
    public function addMine(Mine $area): void {
        $this->mines[] = $area;
    }

    public function addBadlands(BadlandsRegion $region) : void
    {
        $this->badlands[] = $region;
    }

    /**
     * @param Position $position
     *
     * @return Zone|null
     */
    public function getZoneInPosition(Position $position): ?Zone {
        if(!empty($this->core->getServerManager()->getAreaManager()->getAreasInPosition($position))) {
            return null;
        }
        foreach($this->zones as $zone) {
            if($zone->isPositionInside($position) === true) {
                return $zone;
            }
        }
        return null;
    }

    /**
     * @param Position $position
     * @return BadlandsRegion|null
     */
    public function getBadlandsInPosition(Position $position) : ?BadlandsRegion
    {
        foreach ($this->badlands as $region) {
            if($region->isPositionInside($position) === true) return $region;
        }

        return null;
    }

    /**
     * @param int $tier
     * @return BadlandsRegion|null
     */
    public function getBadlandsByTier(int $tier) : ?BadlandsRegion
    {
        foreach ($this->badlands as $region) {
            if($region->getTier() === $tier) return $region;
        }

        return null;
    }

    /**
     * @return BadlandsRegion[]
     */
    public function getBadlands() : array
    {
        return $this->badlands;
    }

    /**
     * @param int $tier
     *
     * @return int
     */
    public static function getArmorMaxLevel(int $tier): int {
        return match ($tier) {
            Armor::TIER_CHAIN => 11,
            Armor::TIER_GOLD => 21,
            Armor::TIER_IRON => 31,
            default => 41,
        };
    }

    /**
     * @param int $tier
     *
     * @return int
     */
    public static function getWeaponMaxLevel(int $tier): int {
        return match ($tier) {
            ToolTier::WOOD()->id() => 11,
            ToolTier::STONE()->id() => 16,
            ToolTier::GOLD()->id() => 21,
            ToolTier::IRON()->id() => 31,
            default => 41,
        };
    }

    /**
     * @param int $slot
     * @param int $tier
     *
     * @return int
     */
    public static function getArmorPoints(int $slot, int $tier): int {
        switch($slot) {
            case Armor::SLOT_HEAD:
                if($tier === Armor::TIER_CHAIN) {
                    return 2;
                }
                elseif($tier === Armor::TIER_GOLD) {
                    return 2;
                }
                elseif($tier === Armor::TIER_IRON) {
                    return 2;
                }
                elseif($tier === Armor::TIER_DIAMOND || $tier === Armor::TIER_LEATHER) {
                    return 3;
                }
                break;
            case Armor::SLOT_CHESTPLATE:
                if($tier === Armor::TIER_CHAIN) {
                    return 5;
                }
                elseif($tier === Armor::TIER_GOLD) {
                    return 5;
                }
                elseif($tier === Armor::TIER_IRON) {
                    return 6;
                }
                elseif($tier === Armor::TIER_DIAMOND || $tier === Armor::TIER_LEATHER) {
                    return 8;
                }
                break;
            case Armor::SLOT_LEGGINGS:
                if($tier === Armor::TIER_CHAIN) {
                    return 4;
                }
                elseif($tier === Armor::TIER_GOLD) {
                    return 3;
                }
                elseif($tier === Armor::TIER_IRON) {
                    return 5;
                }
                elseif($tier === Armor::TIER_DIAMOND || $tier === Armor::TIER_LEATHER) {
                    return 6;
                }
                break;
            case Armor::SLOT_BOOTS:
                if($tier === Armor::TIER_CHAIN) {
                    return 1;
                }
                elseif($tier === Armor::TIER_GOLD) {
                    return 1;
                }
                elseif($tier === Armor::TIER_IRON) {
                    return 2;
                }
                elseif($tier === Armor::TIER_DIAMOND || Armor::TIER_LEATHER) {
                    return 3;
                }
                break;
        }
        return 0;
    }

    /**
     * @return Zone[]
     */
    public function getZones(): array {
        return $this->zones;
    }

    /**
     * @return Mine[]
     */
    public function getMines(): array {
        return $this->mines;
    }
}