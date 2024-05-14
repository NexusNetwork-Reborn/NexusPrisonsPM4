<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\event\OpenItemEvent;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\game\item\types\task\ShardAnimationTask;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\TranslationException;
use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class Shard extends Interactive {

    const SHARD = "Shard";

    /** @var string */
    private $rarity;

    /**
     * Shard constructor.
     *
     * @param string $rarity
     */
    public function __construct(string $rarity) {
        $customName = TextFormat::RESET . TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "$rarity Shard";
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "A powerful relic";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "that may contain treasure!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Contains " . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "$rarity Rarity" . TextFormat::GRAY . " items...";
        $rewards = Nexus::getInstance()->getGameManager()->getRewardsManager()->getShard($rarity)->getRewards();
        foreach($rewards as $reward) {
            $lore[] = TextFormat::RESET . TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$rarity] . " * " . TextFormat::RESET . TextFormat::GRAY . TextFormat::clean($reward->getName());
        }
        $this->rarity = $rarity;
        parent::__construct(VanillaItems::PRISMARINE_SHARD(), $customName, $lore);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::SHARD => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return Shard|mixed
     */
    public static function fromItem(Item $item) {
        $tag = self::getCustomTag($item);
        $rarity = $tag->getString(self::SHARD);
        return new self($rarity);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::SHARD, $this->rarity);
        return $tag;
    }

    /**
     * @return string
     */
    public function getRarity(): string {
        return $this->rarity;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param CompoundTag $tag
     * @param int $face
     * @param Block $blockClicked
     *
     * @throws TranslationException
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        $slots = $player->getInventory()->getSize() - count($player->getInventory()->getContents());
        $count = $item->getCount() > $slots ? $slots : $item->getCount();
        if($count <= 0) {
            $player->sendTranslatedMessage("fullInventory");
            return;
        }
        $inventory->setItemInHand($item->setCount($item->getCount() - $count));
        $player->playDingSound();
        $rarity = $this->getRarity();
        $rewards = Nexus::getInstance()->getGameManager()->getRewardsManager()->getShard($rarity);
        $player->getCore()->getScheduler()->scheduleRepeatingTask(new ShardAnimationTask($player, $rarity, $rewards, $count), 1);
        $event = new OpenItemEvent($player, $this);
        $event->call();
    }
}