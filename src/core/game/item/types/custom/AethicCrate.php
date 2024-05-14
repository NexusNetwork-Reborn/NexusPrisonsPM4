<?php

declare(strict_types=1);

namespace core\game\item\types\custom;

use core\display\animation\entity\CrateEntity;
use core\game\item\types\Interactive;
use core\game\rewards\types\MonthlyRewards;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class AethicCrate extends Interactive {

    const MONTH = "Month";

    const YEAR = "Year";

    /** @var StringTag */
    private $month;

    /** @var int */
    private $year;

    /**
     * AethicCrate constructor.
     *
     * @param string $month
     * @param int $year
     * @param string|null $uuid
     */
    public function __construct(string $month, int $year, ?string $uuid = null) {
        $this->month = $month;
        $this->year = $year;
        $rewards = Nexus::getInstance()->getGameManager()->getRewardsManager()->getMonthly($this->year, $this->month);
        $customName = $rewards->getColoredName();
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "You can unlock this";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "  at " . TextFormat::RESET . TextFormat::AQUA . "nexusprisons.tebex.io";
        if(!empty($rewards->getAdminItems())) {
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . "ADMIN ITEMS";
            foreach($rewards->getAdminItems() as $item) {
                $lore[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . $item->getName();
            }
        }
        if(!empty($rewards->getCosmetics())) {
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "COSMETICS";
            foreach($rewards->getCosmetics() as $item) {
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::YELLOW . $item->getName();
            }
        }
        if(!empty($rewards->getTreasureItems())) {
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "TREASURE ITEMS";
            foreach($rewards->getTreasureItems() as $item) {
                $lore[] = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::GOLD . $item->getName();
            }
        }
        if(!empty($rewards->getBonus())) {
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::BLUE . TextFormat::BOLD . "BONUS";
            foreach($rewards->getBonus() as $item) {
                $lore[] = TextFormat::RESET . TextFormat::BLUE . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::BLUE . $item->getName();
            }
        }
        parent::__construct(VanillaBlocks::ENDER_CHEST()->asItem(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::MONTH => StringTag::class,
            self::YEAR => ShortTag::class
        ];
    }


    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $month = $tag->getString(self::MONTH);
        $year = $tag->getShort(self::YEAR);
        $uuid = $tag->getString(self::UUID);
        return new self($month, $year, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::MONTH, $this->month);
        $tag->setShort(self::YEAR, $this->year);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param int $face
     * @param Block $block
     */
    public function onInteract(NexusPlayer $player, Inventory $inventory, Item $item, int $face, Block $block): void {
        if($block->isSolid()) {
            $rewards = Nexus::getInstance()->getGameManager()->getRewardsManager()->getMonthly($this->year, $this->month);
            if(Nexus::getInstance()->getServerManager()->getAreaManager()->canDamage($player->getPosition())) {
                $player->sendTranslatedMessage("inWarzone");
                $player->playErrorSound();
                return;
            }
            $world = $block->getPosition()->getWorld();
            $position = Position::fromObject($block->getPosition()->floor()->add(0, 1, 0), $world);
            if($world !== null) {
                CrateEntity::create($player, $rewards, Position::fromObject($position->add(0.5, 0, 0.5), $player->getWorld()))->spawnToAll();
                $inventory->setItemInHand($item->setCount($item->getCount() - 1));
            }
        }
    }
}