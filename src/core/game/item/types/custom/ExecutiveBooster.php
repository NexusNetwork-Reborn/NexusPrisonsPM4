<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\command\inventory\WarpMenuInventory;
use core\game\item\event\ApplyItemEvent;
use core\game\item\types\Interactive;
use core\player\NexusPlayer;
use core\translation\Translation;
use libs\utils\Utils;
use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;

class ExecutiveBooster extends Interactive {

    const EXECUTIVE_BOOSTER = "ExecutiveBooster";

    const TIME = "Time";

    /** @var int */
    private $time;

    /**
     * ExecutiveBooster constructor.
     *
     * @param int $minutes
     * @param string|null $uuid
     */
    public function __construct(int $minutes, ?string $uuid = null) {
        $customName = TextFormat::RESET . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Executive Booster";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Be able to obtain and upgrade";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "to Executive Enchantments";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Books and Pickaxe Points)";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "DURATION";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . $minutes . " minutes";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right click to apply";
        $this->time = $minutes * 60;
        parent::__construct(VanillaItems::EMERALD(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::EXECUTIVE_BOOSTER => StringTag::class,
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
        $time /= 60;
        $uuid = $tag->getString(self::UUID);
        return new self((int)$time, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::EXECUTIVE_BOOSTER, self::EXECUTIVE_BOOSTER);
        $tag->setLong(self::TIME, $this->time);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return int
     */
    public function getSecond(): int {
        return $this->time;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        if(!isset(WarpMenuInventory::$executiveSessions[$player->getXuid()])) {
            $player->sendMessage(TextFormat::RED . "You are not currently in the Executive Mine!");
            return;
        }
        $task = WarpMenuInventory::$executiveSessions[$player->getXuid()];
        $time = $this->getSecond();
        $task->addTimeLeft($this->time);
        $player->sendMessage(Translation::ORANGE . "You now have an active Executive Booster for: " . TextFormat::GREEN . Utils::secondsToTime($time));
        $player->getWorld()->addSound($player->getPosition(), new BlazeShootSound(), [$player]);
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $this->setUsed();
        $event = new ApplyItemEvent($player, $this);
        $event->call();
    }
}