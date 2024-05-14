<?php
declare(strict_types=1);

namespace core\level\block;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Pickaxe;
use core\level\entity\types\Powerball;
use core\level\LevelManager;
use core\player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockToolType;
use pocketmine\block\NetherQuartzOre;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\ToolTier;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\BlockTransaction;
use core\game\item\sets\utils\SetUtils;

class CrudeOre extends NetherQuartzOre {

    /** @var int */
    private $ore = 0;

    /** @var string */
    private $rarity = Rarity::SIMPLE;

    /** @var bool */
    private $refined = false;

    /** @var float */
    private $amount = 0;

    /** @var null|int */
    private $sources = null;

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
        if($tile instanceof \core\level\tile\CrudeOre) {
            $this->ore = $tile->getOre();
            $this->rarity = $tile->getRarity();
            $this->refined = $tile->isRefined();
            $this->amount = $tile->getAmount();
            $this->sources = $tile->getSources();
        }
    }

    public function writeStateToWorld(): void {
        parent::writeStateToWorld();
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        assert($tile instanceof \core\level\tile\CrudeOre);
        $tile->setOre($this->ore);
        $tile->setRarity($this->rarity);
        $tile->setRefined($this->refined);
        $tile->setAmount($this->amount);
        $tile->setSources($this->sources);
    }

    /**
     * @param Item $item
     * @param Player|null $player
     *
     * @return bool
     */
    public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null): bool {
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if($player instanceof NexusPlayer) {
            if($tile instanceof \core\level\tile\CrudeOre) {
                $rarity = $tile->getRarity();
                $refined = $tile->isRefined();
                $multiplier = Rarity::getCrudeOreMultiplier($rarity);
                if(!$refined) {
                    $name = TextFormat::RESET . TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "$rarity Crude Ore";
                }
                else {
                    $name = TextFormat::RESET . TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "$rarity Refined Crude Ore";
                }
                $sources = $tile->getSources();
                if($sources === null) {
                    $num = "?";
                }
                else {
                    $num = number_format($sources);
                }
                $player->sendAlert($name . TextFormat::RESET . TextFormat::GOLD . " at " . TextFormat::GREEN . TextFormat::BOLD . $multiplier . "x" . TextFormat::RESET . TextFormat::GOLD . " speed from " . TextFormat::WHITE . TextFormat::BOLD . $num . TextFormat::RESET . TextFormat::GOLD . " sources.");
                if($sources !== null) {
                    $ore = $tile->getOre();
                    $color = TextFormat::WHITE;
                    $item = ItemFactory::getInstance()->get($ore);
                    $amount = $tile->getAmount();
                    switch($ore) {
                        case BlockLegacyIds::COAL_ORE:
                            if($refined) {
                                $item = ItemFactory::getInstance()->get(ItemIds::COAL);
                            }
                            else {
                                $item = ItemFactory::getInstance()->get(ItemIds::COAL_ORE);
                            }
                            $color = TextFormat::DARK_GRAY;
                            break;
                        case BlockLegacyIds::IRON_ORE:
                            if($refined) {
                                $item = ItemFactory::getInstance()->get(ItemIds::IRON_INGOT);
                            }
                            else {
                                $item = ItemFactory::getInstance()->get(ItemIds::IRON_ORE);
                            }
                            $color = TextFormat::GRAY;
                            break;
                        case BlockLegacyIds::LAPIS_ORE:
                            if($refined) {
                                $item = ItemFactory::getInstance()->get(ItemIds::DYE, 4);
                            }
                            else {
                                $item = ItemFactory::getInstance()->get(ItemIds::LAPIS_ORE);
                            }
                            $color = TextFormat::DARK_BLUE;
                            break;
                        case BlockLegacyIds::REDSTONE_ORE:
                            if($refined) {
                                $item = ItemFactory::getInstance()->get(ItemIds::REDSTONE);
                            }
                            else {
                                $item = ItemFactory::getInstance()->get(ItemIds::REDSTONE_ORE);
                            }
                            $color = TextFormat::RED;
                            break;
                        case BlockLegacyIds::GOLD_ORE:
                            if($refined) {
                                $item = ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT);
                            }
                            else {
                                $item = ItemFactory::getInstance()->get(ItemIds::GOLD_ORE);
                            }
                            $color = TextFormat::YELLOW;
                            break;
                        case BlockLegacyIds::DIAMOND_ORE:
                            if($refined) {
                                $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND);
                            }
                            else {
                                $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_ORE);
                            }
                            $color = TextFormat::AQUA;
                            break;
                        case BlockLegacyIds::EMERALD_ORE:
                            if($refined) {
                                $item = ItemFactory::getInstance()->get(ItemIds::EMERALD);
                            }
                            else {
                                $item = ItemFactory::getInstance()->get(ItemIds::EMERALD_ORE);
                            }
                            $color = TextFormat::GREEN;
                            break;
                    }
                    $player->sendAlert(TextFormat::GOLD . "Enriched with " . $color . TextFormat::BOLD . $item->getName() . TextFormat::RESET . TextFormat::GOLD . " that has generated " . TextFormat::WHITE . TextFormat::BOLD . number_format($amount, 2) . TextFormat::RESET . TextFormat::GOLD . " so far.");
                }
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
        if(!\core\game\item\types\custom\CrudeOre::isInstanceOf($item)) {
            return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
        }
        $crudeOre = \core\game\item\types\custom\CrudeOre::fromItem($item);
        $rarity = $crudeOre->getRarity();
        if($player instanceof NexusPlayer) {
            $levelNeeded = ItemManager::getLevelToUseRarity($rarity);
            if($player->getDataSession()->getTotalXPLevel() < $levelNeeded) {
                if($player->getDataSession()->getPrestige() <= 0) {
                    $player->playErrorSound();
                    $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "You must be level $levelNeeded!");
                    return false;
                }
            }
        }
        $this->rarity = $rarity;
        $this->refined = $crudeOre->isRefined();
        return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }

    /**
     * @param int $ore
     *
     * @return int
     */
    public static function getRangeByOre(int $ore): int {
        switch($ore) {
            case BlockLegacyIds::COAL_ORE:
            case BlockLegacyIds::IRON_ORE:
            case BlockLegacyIds::LAPIS_ORE:
                return 3;
                break;
            case BlockLegacyIds::REDSTONE_ORE:
            case BlockLegacyIds::GOLD_ORE:
                return 2;
                break;
            default:
                return 1;
        }
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
        $drops = [ItemFactory::getInstance()->get(ItemIds::NETHER_QUARTZ, 0, 1)];
        if($tile instanceof \core\level\tile\CrudeOre) {
            $drops = [(new \core\game\item\types\custom\CrudeOre($tile->getRarity()))->toItem()->setCount(1)];
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

    public function onScheduledUpdate(): void {
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if($tile instanceof \core\level\tile\CrudeOre) {
            $tile->recalculate();
            $this->getPosition()->getWorld()->scheduleDelayedBlockUpdate($this->getPosition(), 300);
        }
    }
}
