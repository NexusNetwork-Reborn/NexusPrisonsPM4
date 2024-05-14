<?php
declare(strict_types=1);

namespace core\level\block;

use core\game\item\ItemManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\server\announcement\task\RestartTask;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockToolType;
use pocketmine\block\Opaque;
use pocketmine\item\Item;
use pocketmine\item\ToolTier;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\BlockTransaction;

class OreGenerator extends Opaque {

    /** @var int */
    protected $ore;

    /** @var int */
    private $stack = 1;

    /** @var bool */
    private $updated = false;

    /**
     * CrudeOre constructor.
     *
     * @param BlockIdentifier $idInfo
     * @param string $name
     */
    public function __construct(BlockIdentifier $idInfo, string $name) {
        parent::__construct($idInfo, $name, new BlockBreakInfo(5.0, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel()));
    }

    public function readStateFromWorld(): void {
        parent::readStateFromWorld();
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if($tile instanceof \core\level\tile\OreGenerator) {
            $this->ore = $tile->getOre();
            $this->stack = $tile->getStack();
        }
    }

    public function writeStateToWorld(): void {
        parent::writeStateToWorld();
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        assert($tile instanceof \core\level\tile\OreGenerator);
        $tile->setOre($this->ore);
        $tile->setStack($this->stack);
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
            if($tile instanceof \core\level\tile\OreGenerator) {
                $stack = $tile->getStack();
                $ore = $tile->getOre();
                $num = number_format($stack);
                $color = TextFormat::WHITE;
                $block = BlockFactory::getInstance()->get($ore, 0);
                switch($ore) {
                    case BlockLegacyIds::COAL_ORE:
                        $color = TextFormat::DARK_GRAY;
                        break;
                    case BlockLegacyIds::IRON_ORE:
                        $color = TextFormat::GRAY;
                        break;
                    case BlockLegacyIds::LAPIS_ORE:
                        $color = TextFormat::DARK_BLUE;
                        break;
                    case BlockLegacyIds::REDSTONE_ORE:
                        $color = TextFormat::RED;
                        break;
                    case BlockLegacyIds::GOLD_ORE:
                        $color = TextFormat::YELLOW;
                        break;
                    case BlockLegacyIds::DIAMOND_ORE:
                        $color = TextFormat::AQUA;
                        break;
                    case BlockLegacyIds::EMERALD_ORE:
                        $color = TextFormat::GREEN;
                        break;
                }
                $player->sendAlert(TextFormat::GOLD . "There are currently " . $color . TextFormat::BOLD . $block->getName() . " Generators" . TextFormat::GOLD . " * " . TextFormat::WHITE . $num . TextFormat::RESET . TextFormat::GOLD . " pumping ores.");
            }
        }
        return true;
    }

    /**
     * @param BlockTransaction $tx
     * @param Item $item
     * @param Block $blockReplace
     * @param Block $blockClicked
     * @param int $face
     * @param Vector3 $clickVector
     * @param Player|null $player
     *
     * @return bool
     */
    public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool {
        if(!\core\game\item\types\custom\OreGenerator::isInstanceOf($item)) {
            return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
        }
        $generator = \core\game\item\types\custom\OreGenerator::fromItem($item);
        $ore = $generator->getOre();
        if($player instanceof NexusPlayer) {
            $levelNeeded = ItemManager::getLevelToMineOre($ore);
            if($player->getDataSession()->getTotalXPLevel() < $levelNeeded) {
                if($player->getDataSession()->getPrestige() <= 0) {
                    $player->playErrorSound();
                    $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "You must be level $levelNeeded");
                }
                return false;
            }
        }
        $this->stack = 1;
        $this->ore = $ore->getId();
        return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
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
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        $drops = parent::getDrops($item);
        if($tile instanceof \core\level\tile\OreGenerator and $tile->getOre() !== null and $tile->getOre() !== -1) {
            $drops = [(new \core\game\item\types\custom\OreGenerator(BlockFactory::getInstance()->get($tile->getOre(), 0)))->toItem()->setCount($tile->getStack())];
        }
        return $drops;
    }

    public function onScheduledUpdate(): void {
        if(!$this->updated) {
            $this->getPosition()->getWorld()->scheduleDelayedBlockUpdate($this->getPosition(), 1200);
            $this->updated = true;
            return;
        }
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if($tile instanceof \core\level\tile\OreGenerator) {
            $tile->process(1);
            $this->getPosition()->getWorld()->scheduleDelayedBlockUpdate($this->getPosition(), 1200);
        }
    }
}
