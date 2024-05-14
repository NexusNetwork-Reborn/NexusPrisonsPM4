<?php

namespace core\level\tile;

use core\Nexus;
use core\player\NexusPlayer;
use customiesdevs\customies\block\CustomiesBlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Spawnable;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\SmokeParticle;
use pocketmine\world\sound\FireExtinguishSound;
use pocketmine\world\World;

class RushOreBlock extends Spawnable {

    private const FLOATING_ID = "FloatingID";

    private const HITS = "Hits";

    private string $floatingIdentifier = "";

    private int $hits = 100;

    public static $enabledFloatings = [];

    //private const IDS = [BlockLegacyIds::COAL_BLOCK, BlockLegacyIds::IRON_BLOCK, BlockLegacyIds::LAPIS_BLOCK, BlockLegacyIds::REDSTONE_BLOCK, BlockLegacyIds::GOLD_BLOCK, BlockLegacyIds::DIAMOND_BLOCK, BlockLegacyIds::EMERALD_BLOCK];

    public function __construct(World $world, Vector3 $pos)
    {
        $IDS = [BlockLegacyIds::GRAY_GLAZED_TERRACOTTA, BlockLegacyIds::SILVER_GLAZED_TERRACOTTA, BlockLegacyIds::YELLOW_GLAZED_TERRACOTTA, BlockLegacyIds::LIGHT_BLUE_GLAZED_TERRACOTTA, BlockLegacyIds::GREEN_GLAZED_TERRACOTTA, BlockLegacyIds::BLUE_GLAZED_TERRACOTTA, BlockLegacyIds::RED_GLAZED_TERRACOTTA];
        parent::__construct($world, $pos);
//        if(!in_array($this->getBlock()->getId(), self::IDS)) {
//            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void {
//                $this->close();
//            }), 1);
//            return;
//        }
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($IDS) : void {
            if(!in_array($this->getBlock()->getId(), $IDS)) {
                $this->close();
                return;
            }
            $this->setFloatingText($this->getBlock()->getId());
            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void {
                $block = $this->getPosition()->getWorld()->getBlock($this->getPosition());
                if($block instanceof \core\level\block\RushOreBlock) {
                    $block->onDeletionUpdate();
                }
            }), 600 * 20);
        }), 5);
    }

    public function setFloatingText(int $blockId) {
//        $this->clearFloatingText();
//        $this->setFloatingIdentifier(uniqid("", true));
//        $info = $this->getInfoByID($blockId);
//        $pos = $this->getPosition();
//        $pos->y += 2;
//        foreach ($this->getPosition()->getWorld()->getServer()->getOnlinePlayers() as $player) {
//            if($player instanceof NexusPlayer) {
//                $player->addFloatingText($pos, $this->getFloatingIdentifier(), $info);
//            }
//        }
//        self::$enabledFloatings[$this->getFloatingIdentifier()] = [$info, $pos];
    }

    public function clearFloatingText() : void {
//        if($this->getFloatingIdentifier() !== "") {
//            foreach ($this->getPosition()->getWorld()->getServer()->getOnlinePlayers() as $player) {
//                if($player instanceof NexusPlayer && $player->getFloatingText($this->getFloatingIdentifier()) !== null) {
//                    $player->removeFloatingText($this->getFloatingIdentifier());
//                }
//            }
//            unset(self::$enabledFloatings[$this->getFloatingIdentifier()]);
//            $this->setFloatingIdentifier("");
//        }
    }

    public function getInfoByID(int $id) : string {
        $base = $this->getBaseInfo($id);
        $swing = $this->getHits() > 1 ? "swings" : "swing";
        $base .= TextFormat::RESET . TextFormat::GRAY . " (Mine)";
        return $base . "\n" . TextFormat::BOLD . TextFormat::AQUA . TextFormat::UNDERLINE . $this->getRewardMultiplierByID($id) . "x" . TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . " XP and Energy in $this->hits $swing!";
    }

    public function getBaseInfo(int $id, bool $prefix = false) : string {
        if(!$prefix) {
            return match ($id) {
                BlockLegacyIds::GRAY_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::DARK_GRAY . "COAL RUSH",
                BlockLegacyIds::SILVER_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::WHITE . "IRON RUSH",
                BlockLegacyIds::YELLOW_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::GOLD . "GOLD RUSH",
                BlockLegacyIds::LIGHT_BLUE_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::AQUA . "DIAMOND RUSH",
                BlockLegacyIds::GREEN_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::GREEN . "EMERALD RUSH",
                BlockLegacyIds::RED_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::RED . "REDSTONE RUSH",
                BlockLegacyIds::BLUE_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::BLUE . "LAPIS RUSH",
            };
        } else {
            return match ($id) {
                BlockLegacyIds::GRAY_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::DARK_GRAY . "(!) COAL RUSH" . TextFormat::RESET . TextFormat::DARK_GRAY,
                BlockLegacyIds::SILVER_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::WHITE . "(!) IRON RUSH" . TextFormat::RESET . TextFormat::WHITE,
                BlockLegacyIds::YELLOW_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::GOLD . "(!) GOLD RUSH" . TextFormat::RESET . TextFormat::GOLD,
                BlockLegacyIds::LIGHT_BLUE_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::AQUA . "(!) DIAMOND RUSH" . TextFormat::RESET . TextFormat::AQUA,
                BlockLegacyIds::GREEN_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::GREEN . "(!) EMERALD RUSH" . TextFormat::RESET . TextFormat::GREEN,
                BlockLegacyIds::RED_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::RED . "(!) REDSTONE RUSH" . TextFormat::RESET . TextFormat::RED,
                BlockLegacyIds::BLUE_GLAZED_TERRACOTTA => TextFormat::BOLD . TextFormat::BLUE . "(!) LAPIS RUSH" . TextFormat::RESET . TextFormat::BLUE,
            };
        }
    }

    public function getColor(int $id) : string {
        return match ($id) {
            BlockLegacyIds::GRAY_GLAZED_TERRACOTTA => TextFormat::DARK_GRAY,
            BlockLegacyIds::SILVER_GLAZED_TERRACOTTA => TextFormat::WHITE,
            BlockLegacyIds::YELLOW_GLAZED_TERRACOTTA => TextFormat::GOLD,
            BlockLegacyIds::LIGHT_BLUE_GLAZED_TERRACOTTA => TextFormat::AQUA,
            BlockLegacyIds::GREEN_GLAZED_TERRACOTTA => TextFormat::GREEN,
            BlockLegacyIds::RED_GLAZED_TERRACOTTA => TextFormat::RED,
            BlockLegacyIds::BLUE_GLAZED_TERRACOTTA => TextFormat::BLUE,
        };
    }

    public function getRewardMultiplierByID(int $id) : int {
        return match($id) {
            BlockLegacyIds::GRAY_GLAZED_TERRACOTTA => 50, //COAL
            BlockLegacyIds::SILVER_GLAZED_TERRACOTTA => 150, //IRON
            BlockLegacyIds::YELLOW_GLAZED_TERRACOTTA => 1750, //GOLD
            BlockLegacyIds::LIGHT_BLUE_GLAZED_TERRACOTTA => 2500, //DIAMOND
            BlockLegacyIds::GREEN_GLAZED_TERRACOTTA => 3000, //EMERALD
            BlockLegacyIds::RED_GLAZED_TERRACOTTA => 800, //REDSTONE
            BlockLegacyIds::BLUE_GLAZED_TERRACOTTA => 400, //LAPIS
        };
    }

    /**
     * @param string $floatingIdentifier
     */
    public function setFloatingIdentifier(string $floatingIdentifier): void
    {
        $this->floatingIdentifier = $floatingIdentifier;
    }

    /**
     * @return string
     */
    public function getFloatingIdentifier(): string
    {
        return $this->floatingIdentifier;
    }

    public function getHits(): int
    {
        return $this->hits;
    }

    public function addHit(): void
    {
        $this->hits--;
        if($this->hits > 0) {
            $this->setFloatingText($this->getBlock()->getId());
        } else {
            $this->clearFloatingText();
        }
    }

    public function setHits(int $hits): void
    {
        $this->hits = $hits;
        if($this->hits > 0) {
            $this->setFloatingText($this->getBlock()->getId());
        } else {
            $this->clearFloatingText();
        }
    }

    public function readSaveData(CompoundTag $nbt): void
    {
        if(!$nbt->getTag(self::FLOATING_ID) instanceof StringTag) {
            $nbt->setInt(self::FLOATING_ID, $this->floatingIdentifier);
        }
        $this->floatingIdentifier = $nbt->getString(self::FLOATING_ID);
        if(!$nbt->getTag(self::HITS) instanceof IntTag) {
            $nbt->setInt(self::HITS, $this->hits);
        }
        $this->hits = $nbt->getInt(self::HITS, $this->hits);
    }

    private function decay() {
        $world = $this->getPosition()->getWorld();
        $block = $this->getBlock();
        if ($block instanceof \core\level\block\RushOreBlock && $this->getHits() === 100) {
            $this->clearFloatingText();
            $world->setBlock($this->getPosition(), $block->getNormalOreBlock());
            $world->addParticle($this->getPosition()->add(0, 1, 0), new SmokeParticle());
            $world->addSound($this->getPosition(), new FireExtinguishSound());
            // TODO: Remove tile? Will it clear the floating text.
        }
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setString(self::FLOATING_ID, $this->floatingIdentifier);
        $nbt->setInt(self::HITS, $this->hits);
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt): void
    {
        $nbt->setString(self::FLOATING_ID, $this->floatingIdentifier);
        $nbt->setInt(self::HITS, $this->hits);
    }

    protected function onBlockDestroyedHook(): void
    {
        $this->clearFloatingText();
    }
}