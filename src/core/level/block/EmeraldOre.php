<?php
declare(strict_types=1);

namespace core\level\block;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\vanilla\Pickaxe;
use core\level\entity\types\Powerball;
use core\level\LevelManager;
use core\level\task\BlockRegenerateTask;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockToolType;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\ToolTier;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BlockPlaceSound;
use core\game\item\sets\utils\SetUtils;

class EmeraldOre extends \pocketmine\block\EmeraldOre implements Ore {

    /**
     * EmeraldOre constructor.
     *
     * @param BlockIdentifier $idInfo
     * @param string $name
     */
    public function __construct(BlockIdentifier $idInfo, string $name) {
        parent::__construct($idInfo, $name, new BlockBreakInfo(3.0, BlockToolType::PICKAXE, ToolTier::DIAMOND()->getHarvestLevel()));
    }

    /**
     * @return int
     */
    public function getXPDrop(): int {
        return mt_rand(2300, 2600);
    }

    /**
     * @return int
     */
    public function getEnergyDrop(): int {
        return mt_rand(1700, 2100);
    }

    /**
     * @param Item $item
     *
     * @return array
     */
    public function getDropsForCompatibleTool(Item $item): array {
        $drops = [];
        $chance = 20;
        $chance -= $item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::ENRICH)) * 2;
        if(mt_rand(1, $chance) == 1) {
            $drops[] = ItemFactory::getInstance()->get(ItemIds::EMERALD, 0, 1);
        }
        else {
            $drops[] = ItemFactory::getInstance()->get(ItemIds::EMERALD_ORE, 0, 1);
        }
        return $drops;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
     public function onEntityInside(Entity $entity): bool {
        if($entity instanceof Powerball) {
            $owner = $entity->getOwningEntity();
            if($owner instanceof NexusPlayer) {
                $item = $owner->getInventory()->getItemInHand();
                if($item instanceof Pickaxe and ($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::POWERBALL)) || SetUtils::isWearingFullSet($owner, "koth"))) {
                    LevelManager::$blockBreaks[$owner->getXuid()] = $this->getPosition();
                    $owner->getWorld()->useBreakOn($this->getPosition(), $item, $owner);
                }
            }
        }
        return parent::onEntityInside($entity);
    }

    /**
     * @return bool
     */
    public function hasEntityCollision(): bool {
        return true;
    }

    /**
     * @param Item $item
     * @param Player|null $player
     *
     * @return bool
     */
    public function onBreak(Item $item, Player $player = null): bool {
        if($player instanceof NexusPlayer) {
            if($player->isCreative(true)) {
                return parent::onBreak($item, $player);
            }
        }
        if($this->getBreakInfo()->isToolCompatible($item)) {
            if($player instanceof NexusPlayer) {
                $level = $item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::REPLENISH));
                if($level > 0 and (!$player->getCESession()->hasExplode())) {
                    $level *= $player->getCESession()->getItemLuckModifier();
                    if($level >= mt_rand(1, 70)) {
                        $distance = $player->getPosition()->distance($this->getPosition());
                        $directionVector = $player->getDirectionVector()->multiply($distance);
                        $position = Position::fromObject($player->getPosition()->add($directionVector->x, $directionVector->y + $player->getEyeHeight() + 1, $directionVector->z), $player->getWorld());
                        $cx = $position->getX();
                        $cy = $position->getY();
                        $cz = $position->getZ();
                        $radius = 0.5;
                        for($i = 0; $i < 11; $i += 1.1) {
                            $x = $cx + ($radius * cos($i));
                            $z = $cz + ($radius * sin($i));
                            $pos = new Vector3($x, $cy, $z);
                            $position->getWorld()->addParticle($pos, new DustParticle(new Color(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255))), [$player]);
                        }
                        $position->getWorld()->addSound($position, new BlockPlaceSound($this));
                        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new BlockRegenerateTask($this, $this->getPosition()), 5);
                        $this->getPosition()->getWorld()->setBlock($this->getPosition(), VanillaBlocks::BEDROCK(), true);
                        return true;
                    }
                }
            }
            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new BlockRegenerateTask($this, $this->getPosition()), 600);
            $this->getPosition()->getWorld()->setBlock($this->getPosition(), VanillaBlocks::BEDROCK(), true);
            return true;
        }
        return false;
    }
}