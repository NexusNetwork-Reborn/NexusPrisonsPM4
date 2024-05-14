<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\game\item\types\task\ContrabandAnimationTask;
use core\game\item\types\task\LootboxAnimationTask;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class Lootbox extends Interactive {

    const NO_EXPIRY = -1;

    const LOOTBOX = "Lootbox";

    const EXPIRATION = "Expiration";

    /** @var string */
    private $lootbox;

    /** @var int */
    private $expirationTime = -1;

    /**
     * Lootbox constructor.
     *
     * @param string $lootbox
     * @param int $expire
     * @param string|null $uuid
     */
    public function __construct(string $lootbox, int $expire = self::NO_EXPIRY, ?string $uuid = null) {
        $lb = Nexus::getInstance()->getGameManager()->getRewardsManager()->getLootbox($lootbox);
        $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . "Lootbox: " . $lb->getColoredName();
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . wordwrap($lb->getLore(), 25, "\n");
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . "Random Loot (" . TextFormat::RESET . TextFormat::GRAY . $lb->getRewardCount() . " items" . TextFormat::BOLD . TextFormat::WHITE . ")";
        foreach($lb->getRewards() as $reward) {
            $reward = $reward->executeCallback();
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . $reward->getCount() . "x " . $reward->getCustomName();
        }
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . "Jackpot Loot";
        foreach($lb->getJackpotLoot() as $reward) {
            $reward = $reward->executeCallback();
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . $reward->getCount() . "x " . $reward->getCustomName();
        }
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . "Bonus Loot (" . TextFormat::RESET . TextFormat::GRAY . count($lb->getBonus()) . " items" . TextFormat::BOLD . TextFormat::WHITE . ")";
        foreach($lb->getBonus() as $reward) {
            $reward = $reward->executeCallback();
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . $reward->getCount() . "x " . $reward->getCustomName();
        }
        if($expire !== -1) {
            $this->expirationTime = $expire;
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Valid until " . TextFormat::RESET . TextFormat::RED . date("n/j/y h:i:s A T", $this->expirationTime);
        }
        $this->lootbox = $lootbox;
        parent::__construct($lb->getDisplay(), $customName, $lore, true, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::LOOTBOX => StringTag::class,
            self::EXPIRATION => IntTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $lootbox = $tag->getString(self::LOOTBOX);
        $expiry = $tag->getInt(self::EXPIRATION, -1);
        $uuid = $tag->getString(self::UUID);
        return new self($lootbox, $expiry, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::LOOTBOX, $this->lootbox);
        $tag->setInt(self::EXPIRATION, $this->expirationTime);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return string
     */
    public function getLootbox(): string {
        return $this->lootbox;
    }

    /**
     * @return int
     */
    public function getExpirationTime(): int {
        return $this->expirationTime;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        if($this->expirationTime !== self::NO_EXPIRY) {
            if(time() >= $this->expirationTime) {
                $player->sendMessage(Translation::RED . "This lootbox has expired!");
                $player->playErrorSound();
                return;
            }
        }
        $lootbox = $this->getLootbox();
        $rewards = Nexus::getInstance()->getGameManager()->getRewardsManager()->getLootbox($lootbox);
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $this->setUsed();
        $player->getCore()->getScheduler()->scheduleRepeatingTask(new LootboxAnimationTask($player, $rewards), 1);
    }
}