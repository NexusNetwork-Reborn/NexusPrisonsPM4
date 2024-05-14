<?php
declare(strict_types=1);

namespace core\level\block;

use core\game\fund\FundManager;
use core\game\item\ItemManager;
use core\level\FakeChunkLoader;
use core\Nexus;
use core\player\NexusPlayer;
use core\server\announcement\task\RestartTask;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockToolType;
use pocketmine\block\Magma;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ToolTier;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\SmokeParticle;
use pocketmine\world\sound\FireExtinguishSound;

class Meteorite extends Magma {

    /** @var int */
    private $stack = 0;

    /** @var bool */
    private $refined = false;

    /** @var bool */
    private $updated = false;

    /**
     * CrudeOre constructor.
     *
     * @param BlockIdentifier $idInfo
     * @param string $name
     */
    public function __construct(BlockIdentifier $idInfo, string $name) {
        parent::__construct($idInfo, $name, new BlockBreakInfo(3.0, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel()));
    }

    public function readStateFromWorld(): void {
        parent::readStateFromWorld();
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if($tile instanceof \core\level\tile\Meteorite) {
            $this->stack = $tile->getStack();
            $this->refined = $tile->isRefined();
        }
    }

    public function writeStateToWorld(): void {
        parent::writeStateToWorld();
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        assert($tile instanceof \core\level\tile\Meteorite);
        $tile->setStack($this->stack);
        $tile->setRefined($this->refined);
    }

    /**
     * @return bool
     */
    public function hasEntityCollision(): bool {
        return false;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function onEntityInside(Entity $entity): bool {
        return true;
    }

    /**
     * @return bool
     */
    public function burnsForever(): bool {
        return false;
    }

    /**
     * @param Item $item
     * @param int $face
     * @param Vector3 $clickVector
     * @param Player|null $player
     *
     * @return bool
     */
    public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null): bool {
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if($player instanceof NexusPlayer) {
            if($tile instanceof \core\level\tile\Meteorite) {
                $stack = $tile->getStack();
                $level = $player->getDataSession()->getTotalXPLevel();
                $block = ItemManager::getOreByLevel($level);
                $color = ItemManager::getColorByOre($block);
                if($tile->isRefined()) {
                    $block = ItemManager::getRefinedDrop($block);
                }
                $player->sendAlert(TextFormat::GOLD . "Enriched with " . TextFormat::WHITE . TextFormat::BOLD . $stack . " " . $color . $block->getName());
            }
        }
        return true;
    }

    /**
     * @return int
     */
    public function getXpDropAmount(): int {
        return 0;
    }

    /**
     * @param Item $item
     *
     * @return Item[]
     */
    public function getDrops(Item $item): array {
        return [];
    }

    public function onScheduledUpdate(): void {
        $world = $this->getPosition()->getWorld();
        $tile = $world->getTile($this->getPosition());
        if($tile instanceof \core\level\tile\Meteorite || $tile instanceof \core\level\tile\OreGenerator) { // TODO: Figue if ore gen is in right place
            if ($tile->getStack() <= 0 or (!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_THREE))) {
                if ($tile instanceof \core\level\tile\Meteorite) {
                    $world->removeTile($tile);
                }
                $world->addParticle($this->getPosition()->add(0, 1, 0), new SmokeParticle());
                $world->addSound($this->getPosition(), new FireExtinguishSound());
                $world->setBlock($this->getPosition(), VanillaBlocks::AIR());
                return;
            }
        }
        if(!$this->updated) {
            $world->scheduleDelayedBlockUpdate($this->getPosition(), 300);
            $this->updated = true;
            return;
        }
        if($tile instanceof \core\level\tile\Meteorite) {
            $tile->decay();
            if($tile->getStack() <= 0) {
                $world->removeTile($tile);
                $world->addParticle($this->getPosition()->add(0, 1, 0), new SmokeParticle());
                $world->addSound($this->getPosition(), new FireExtinguishSound());
                $world->setBlock($this->getPosition(), VanillaBlocks::AIR());
                return;
            }
            $world->scheduleDelayedBlockUpdate($this->getPosition(), 300);
        }
    }
}
