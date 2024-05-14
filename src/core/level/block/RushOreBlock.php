<?php

namespace core\level\block;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\level\task\SpawnRushOreTask;
use core\Nexus;
use core\player\NexusPlayer;
use customiesdevs\customies\block\CustomiesBlockFactory;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Opaque;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\SmokeParticle;
use pocketmine\world\sound\BlockPlaceSound;
use pocketmine\world\sound\FireExtinguishSound;

class RushOreBlock extends Opaque implements Ore
{

    public function __construct(BlockIdentifier $idInfo, string $name, BlockBreakInfo $breakInfo)
    {
        $idInfo = new BlockIdentifier($idInfo->getBlockId(), $idInfo->getVariant(), $idInfo->getItemId(), \core\level\tile\RushOreBlock::class);
        parent::__construct($idInfo, $name, $breakInfo);
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

    public function getXPDrop(): int
    {
        $xp = match ($this->getId()) {
            BlockLegacyIds::GRAY_GLAZED_TERRACOTTA => mt_rand(800, 1200), // COAL
            BlockLegacyIds::SILVER_GLAZED_TERRACOTTA => mt_rand(900, 1400), // IRON
            BlockLegacyIds::YELLOW_GLAZED_TERRACOTTA => mt_rand(1400, 1700), // GOLD
            BlockLegacyIds::LIGHT_BLUE_GLAZED_TERRACOTTA => mt_rand(1800, 2100), // DIAMOND
            BlockLegacyIds::GREEN_GLAZED_TERRACOTTA => mt_rand(2300, 2600), // EMERALD
            BlockLegacyIds::BLUE_GLAZED_TERRACOTTA => mt_rand(1200, 1600), // LAPIS
            BlockLegacyIds::RED_GLAZED_TERRACOTTA => mt_rand(1400, 1800), // REDSTONE
            default => 800,
        };
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if ($tile instanceof \core\level\tile\RushOreBlock && $tile->getHits() == 1) {
            $xp *= $tile->getRewardMultiplierByID($this->getId());
        } else if ($tile === null) {
            $xp *= $this->getRewardMultiplierByID($this->getId());
        }
        return $xp;
    }

    public function getEnergyDrop(): int
    {
        $energy = match ($this->getId()) {
            BlockLegacyIds::GRAY_GLAZED_TERRACOTTA => mt_rand(400, 600),
            BlockLegacyIds::SILVER_GLAZED_TERRACOTTA => mt_rand(500, 800),
            BlockLegacyIds::YELLOW_GLAZED_TERRACOTTA => mt_rand(1200, 1400),
            BlockLegacyIds::LIGHT_BLUE_GLAZED_TERRACOTTA => mt_rand(1500, 1700),
            BlockLegacyIds::GREEN_GLAZED_TERRACOTTA => mt_rand(1700, 2100),
            BlockLegacyIds::BLUE_GLAZED_TERRACOTTA => mt_rand(500, 700),
            BlockLegacyIds::RED_GLAZED_TERRACOTTA => mt_rand(700, 900),
            default => 400,
        };
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if ($tile instanceof \core\level\tile\RushOreBlock && $tile->getHits() == 1) {
            $energy *= $tile->getRewardMultiplierByID($this->getId());
        } else if ($tile === null) {
            $energy *= $this->getRewardMultiplierByID($this->getId());
        }
        return $energy;
    }

    public function getDropsForCompatibleTool(Item $item): array
    {
        $enrichItem = ItemFactory::getInstance()->get(match ($this->getId()) {
            BlockLegacyIds::GRAY_GLAZED_TERRACOTTA => ItemIds::COAL,
            BlockLegacyIds::SILVER_GLAZED_TERRACOTTA => ItemIds::IRON_INGOT,
            BlockLegacyIds::YELLOW_GLAZED_TERRACOTTA => ItemIds::GOLD_INGOT,
            BlockLegacyIds::LIGHT_BLUE_GLAZED_TERRACOTTA => ItemIds::DIAMOND,
            BlockLegacyIds::GREEN_GLAZED_TERRACOTTA => ItemIds::EMERALD,
            BlockLegacyIds::BLUE_GLAZED_TERRACOTTA => ItemIds::DYE,
            BlockLegacyIds::RED_GLAZED_TERRACOTTA => ItemIds::REDSTONE,
            default => ItemIds::COAL,
        }, 0, 1);
        if ($enrichItem->getId() == ItemIds::DYE) {
            $enrichItem = ItemFactory::getInstance()->get(ItemIds::DYE, 4, 1);
        }
        $normalItem = ItemFactory::getInstance()->get(match ($this->getId()) {
            BlockLegacyIds::GRAY_GLAZED_TERRACOTTA => ItemIds::COAL_ORE,
            BlockLegacyIds::SILVER_GLAZED_TERRACOTTA => ItemIds::IRON_ORE,
            BlockLegacyIds::YELLOW_GLAZED_TERRACOTTA => ItemIds::GOLD_ORE,
            BlockLegacyIds::LIGHT_BLUE_GLAZED_TERRACOTTA => ItemIds::DIAMOND_ORE,
            BlockLegacyIds::GREEN_GLAZED_TERRACOTTA => ItemIds::EMERALD_ORE,
            BlockLegacyIds::BLUE_GLAZED_TERRACOTTA => ItemIds::LAPIS_ORE,
            BlockLegacyIds::RED_GLAZED_TERRACOTTA => ItemIds::REDSTONE_ORE,
        }, 0, 1);
        $drops = [];
        $chance = 20;
        $chance -= $item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::ENRICH)) * 2;
        if (mt_rand(1, $chance) == 1) {
            $drops[] = $enrichItem;
        } else {
            $drops[] = $normalItem;
        }
        return $drops;
    }

//    public function onBreak(Item $item, ?Player $player = null): bool
//    {
////        if ($player instanceof NexusPlayer) {
////            if ($player->isCreative(true)) {
////                return parent::onBreak($item, $player);
////            }
////        }
//        if ($this->getBreakInfo()->isToolCompatible($item) && $player !== null) {
//            $position = $this->getPosition();
//            $world = $position->getWorld();
//            $tile = $world->getTile($this->getPosition());
//            if ($tile instanceof \core\level\tile\RushOreBlock) {
//                var_dump("Correct Tile!");
//                $tile->addHit();
//                if ($tile->getHits() > 0) {
//                    $position->getWorld()->addSound($position, new BlockPlaceSound($this));
//                    //$this->getPosition()->getWorld()->setBlock($this->getPosition(), $this, true);
//                } else {
//                    $msg = $tile->getColor($this->getId()) . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::UNDERLINE . $tile->getColor($this->getId()) . $player->getName() . TextFormat::RESET . TextFormat::GRAY . " has mined " . TextFormat::BOLD . $tile->getColor($this->getId()) . $this->getName() . TextFormat::RESET . $tile->getColor($this->getId()) . " won the " . $tile->getBaseInfo($this->getId()) . TextFormat::RESET . $tile->getColor($this->getId()) . " at {$position->getX()}, {$position->getY()}, {$position->getZ()}";
//                    Nexus::getInstance()->getServer()->broadcastMessage($msg);
//                    $world->removeTile($tile);
//                    $tile->close();
//                    $world->setBlock($this->getPosition(), $this->getNormalOreBlock());
//                }
//                return true;
//            }
//            $world->setBlock($this->getPosition(), $this->getNormalOreBlock());
//            return true;
//        }
//        return false;
//    }

    public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null): bool
    {
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if($tile instanceof \core\level\tile\RushOreBlock) {
            $info = $tile->getInfoByID($this->getId());
            if ($player instanceof NexusPlayer) {
                $player->sendAlert($info);
            }
        }
        return true;
    }

    public function onDeletionUpdate(): void
    {
        $world = $this->getPosition()->getWorld();
        $world->addParticle($this->getPosition()->add(0, 1, 0), new SmokeParticle());
        $world->addSound($this->getPosition(), new FireExtinguishSound());
        $world->setBlock($this->getPosition(), $this->getNormalOreBlock());

        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if ($tile instanceof \core\level\tile\RushOreBlock) {
            $world->removeTile($tile);
        }

        $floatingID = serialize($this->getPosition()->asVector3());
        foreach (Nexus::getInstance()->getServer()->getOnlinePlayers() as $player) {
            if($player instanceof NexusPlayer) {
                $player->removeFloatingText($floatingID);
            }
        }
        if(isset(SpawnRushOreTask::$fancyFloaties[$floatingID])) {
            unset(SpawnRushOreTask::$fancyFloaties[$floatingID]);
        }
    }

    public function getNormalOreBlock()
    {
        return BlockFactory::getInstance()->get(match ($this->getId()) {
            BlockLegacyIds::GRAY_GLAZED_TERRACOTTA => BlockLegacyIds::COAL_ORE,
            BlockLegacyIds::SILVER_GLAZED_TERRACOTTA => BlockLegacyIds::IRON_ORE,
            BlockLegacyIds::YELLOW_GLAZED_TERRACOTTA => BlockLegacyIds::GOLD_ORE,
            BlockLegacyIds::LIGHT_BLUE_GLAZED_TERRACOTTA => BlockLegacyIds::DIAMOND_ORE,
            BlockLegacyIds::GREEN_GLAZED_TERRACOTTA => BlockLegacyIds::EMERALD_ORE,
            BlockLegacyIds::BLUE_GLAZED_TERRACOTTA => BlockLegacyIds::LAPIS_ORE,
            BlockLegacyIds::RED_GLAZED_TERRACOTTA => BlockLegacyIds::REDSTONE_ORE,
        }, 0, 1);
    }

}