<?php

declare(strict_types=1);

namespace core\game\item\types\custom;

use core\display\animation\entity\GKitEntity;
use core\display\animation\entity\TorchEntity;
use core\game\item\types\Interactive;
use core\game\kit\GodKit;
use core\game\plots\PlotManager;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class GKitFlare extends Interactive {

    const GKIT = "GKit";

    const FRACTURED = "Fractured";

    /** @var null|GodKit */
    private $kit;

    /** @var bool */
    private $fractured;

    /**
     * GKitFlare constructor.
     *
     * @param GodKit|null $kit
     * @param bool $fractured
     * @param string|null $uuid
     */
    public function __construct(?GodKit $kit = null, bool $fractured = true, ?string $uuid = null) {
        if($kit === null) {
            $lore = [];
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "Causes a random gkit meteorite to fall";
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "wherever you place this!";
            $customName = TextFormat::RESET . TextFormat::DARK_PURPLE . TextFormat::BOLD . "Mystery G-Kit Flare";
            if($fractured) {
                $customName = TextFormat::RESET . TextFormat::DARK_PURPLE . TextFormat::BOLD . "Mystery Fractured G-Kit Flare";
            }
            else {
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Major chance to drop full";
                $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "access to a G-Kit";
            }
        }
        else {
            $color = $kit->getColor();
            $name = $kit->getName();
            $lore = [];
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "Causes the gkit meteorite to fall";
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "wherever you place this!";
            $customName = TextFormat::RESET . $color . TextFormat::BOLD . $name . TextFormat::RESET . $color . " G-Kit Flare";
            if($fractured) {
                $customName = TextFormat::RESET . $color . TextFormat::BOLD . $name . TextFormat::RESET . $color . " Fractured G-Kit Flare";
            }
            else {
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Major chance to drop full";
                $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "access to a G-Kit";
            }
        }
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Place on ground to spawn";
        $this->kit = $kit !== null ? $kit : null;
        $this->fractured = $fractured;
        parent::__construct(VanillaBlocks::REDSTONE_TORCH()->asItem(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::GKIT => StringTag::class,
            self::FRACTURED => ByteTag::class
        ];
    }
    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $kit = $tag->getString(self::GKIT);
        $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName($kit);
        $fractured = (bool)$tag->getByte(self::FRACTURED);
        $uuid = $tag->getString(self::UUID);
        return new self($kit, $fractured, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::GKIT, $this->kit !== null ? $this->kit->getName() : "Mystery");
        $tag->setByte(self::FRACTURED, (int)$this->fractured);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return GodKit|null
     */
    public function getKit(): ?GodKit {
        return $this->kit;
    }

    /**
     * @return bool
     */
    public function isFractured(): bool {
        return $this->fractured;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param int $face
     * @param Block $block
     */
    public function onInteract(NexusPlayer $player, Inventory $inventory, Item $item, int $face, Block $block): void {
        $kit = $this->getKit();
        $fractured = $this->isFractured();
        if($kit === null) {
            $gKits = Nexus::getInstance()->getGameManager()->getKitManager()->getGodKits();
            $kit = $gKits[array_rand($gKits)];
        }
        if($block->isSolid()) {
            $areaManager = Nexus::getInstance()->getServerManager()->getAreaManager();
            $areas = $areaManager->getAreasInPosition($block->getPosition());
            if($areas !== null) {
                foreach($areas as $area) {
                    if($area->getEditFlag() === false) {
                        $player->sendAlert(TextFormat::RED . "You can not spawn a flare in a safe zone!");
                        return;
                    }
                }
            }
            $level = $block->getPosition()->getWorld();
            if(PlotManager::isPlotWorld($level)) {
                $player->sendAlert(TextFormat::RED . "You can not spawn a flare in a safe zone!");
                return;
            }
            if($level !== null) {
                for($i = 1; $i < 30; $i++) {
                    if($level->getBlock(Position::fromObject($block->getPosition()->add(0, $i, 0), $level))->isSolid()) {
                        $player->playErrorSound();
                        $player->sendAlert(TextFormat::RED . "There must be no blocks above where you set your flare!");
                        return;
                    }
                }
                $bbs = $block->getCollisionBoxes();
                if(count($bbs) !== 1) {
                    return;
                }
                $bb = $bbs[0];
                foreach($level->getNearbyEntities($bb->expandedCopy(5, 5, 5)) as $entity) {
                    if($entity instanceof TorchEntity or $entity instanceof GKitEntity) {
                        $player->playErrorSound();
                        $player->sendAlert(TextFormat::RED . "You can only open flares 5 blocks away from each other!");
                        return;
                    }
                }
                $this->setUsed();
                TorchEntity::create($kit, $fractured, Position::fromObject($block->getPosition()->floor()->add(0.5, 3, 0.5), $player->getWorld()))->spawnToAll();
                $inventory->setItemInHand($item->setCount($item->getCount() - 1));
            }
        }
    }
}