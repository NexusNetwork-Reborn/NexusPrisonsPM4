<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

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

class XPBooster extends Interactive {

    const XP_BOOSTER = "XPBooster";

    const MULTIPLIER = "Multiplier";

    const TIME = "Time";

    /** @var float */
    private $multiplier;

    /** @var int */
    private $time;

    /**
     * XPBooster constructor.
     *
     * @param float $multiplier
     * @param int $minutes
     * @param string|null $uuid
     */
    public function __construct(float $multiplier, int $minutes, ?string $uuid = null) {
        $customName = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "XP Booster";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Increase YOUR Mining XP gain";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "for a fixed amount of time";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "MULTIPLIER";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . number_format($multiplier, 2) . "x";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "DURATION";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . $minutes . " minutes";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right click to apply";
        $this->multiplier = $multiplier;
        $this->time = $minutes * 60;
        parent::__construct(VanillaItems::EMERALD(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::XP_BOOSTER => StringTag::class,
            self::MULTIPLIER => FloatTag::class,
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
        $multiplier = $tag->getFloat(self::MULTIPLIER);
        $time = $tag->getLong(self::TIME);
        $time /= 60;
        $uuid = $tag->getString(self::UUID);
        return new self($multiplier, (int)$time, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::XP_BOOSTER, self::XP_BOOSTER);
        $tag->setFloat(self::MULTIPLIER, $this->multiplier);
        $tag->setLong(self::TIME, $this->time);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return float
     */
    public function getMultiplier(): float {
        return $this->multiplier;
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
     * @param CompoundTag $tag
     * @param int $face
     * @param Block $blockClicked
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        $multiplier = $this->getMultiplier();
        $time = $this->getSecond();
        $dataSession = $player->getDataSession();
        $modifier = $dataSession->getBaseXPModifier();
        if($modifier > 1.0) {
            $player->sendMessage(Translation::ORANGE . "You have an active " . number_format($modifier, 2) . "x XP Booster for: " . TextFormat::GREEN . Utils::secondsToTime($player->getDataSession()->getXPBoostTimeLeft()));
            $player->playErrorSound();
            return;
        }
        $this->setUsed();
        $player->sendMessage(Translation::ORANGE . "You now have an active " . number_format($multiplier, 2) . "x XP Booster for: " . TextFormat::GREEN . Utils::secondsToTime($time));
        $dataSession->setXPModifier($multiplier, $time);
        $player->getWorld()->addSound($player->getPosition(), new BlazeShootSound(), [$player]);
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $event = new ApplyItemEvent($player, $this);
        $event->call();
    }
}