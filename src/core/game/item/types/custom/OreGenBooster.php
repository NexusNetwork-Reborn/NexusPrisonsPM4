<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\event\ApplyItemEvent;
use core\game\item\types\Interactive;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;

class OreGenBooster extends Interactive {

    const GENERATOR_BOOSTER = "OreGenBooster";

    const TIME = "Time";

    /** @var int */
    private $time;

    /**
     * OreGenBooster constructor.
     *
     * @param int $minutes
     * @param string|null $uuid
     */
    public function __construct(int $minutes, ?string $uuid = null) {
        $customName = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Ore Gen Booster";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Time warps your Ore Generators";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "into the future causing them";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "to generate ores instantly";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "DURATION";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . $minutes . " minutes";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right click to apply";
        $this->time = $minutes;
        parent::__construct(VanillaItems::EMERALD(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::GENERATOR_BOOSTER => StringTag::class,
            self::TIME => LongTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $time = $tag->getLong(self::TIME);
        $uuid = $tag->getString(self::UUID);
        return new self($time, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::GENERATOR_BOOSTER, self::GENERATOR_BOOSTER);
        $tag->setLong(self::TIME, $this->time);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return int
     */
    public function getMinutes(): int {
        return $this->time;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param CompoundTag $tag
     * @param int $face
     * @param Block $blockClicked
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        $plot = Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotInPosition($player->getPosition());
        if($plot == null) {
            $player->sendMessage(Translation::RED . "You must be in a plot or cell to use this!");
            $player->playErrorSound();
            return;
        }
        $owner = $plot->getOwner();
        if($owner === null or (!$owner->getUser($player->getName()) === null)) {
            $player->sendMessage(Translation::RED . "You don't have access to this plot!");
            $player->playErrorSound();
            return;
        }
        $this->setUsed();
        $player->sendMessage(TextFormat::WHITE . $player->getName() . TextFormat::GOLD . " applied a " . TextFormat::WHITE . "$this->time minutes " . TextFormat::GOLD . "Ore Gen Booster");
        $player->getWorld()->addSound($player->getPosition(), new BlazeShootSound(), [$player]);
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $event = new ApplyItemEvent($player, $this);
        $event->call();
        foreach($plot->getTiles() as $tile) {
            if($tile instanceof \core\level\tile\OreGenerator) {
                $tile->process($this->time);
            }
        }
    }
}