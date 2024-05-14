<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\event\OpenItemEvent;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\game\item\types\task\ContrabandAnimationTask;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class Contraband extends Interactive {

    const CONTRABAND = "Contraband";

    /** @var string */
    private $rarity;

    /**
     * Contraband constructor.
     *
     * @param string $rarity
     * @param string|null $uuid
     */
    public function __construct(string $rarity, ?string $uuid = null) {
        $customName = TextFormat::RESET . TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "$rarity Contraband";
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "A stash of mystery contraband";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "smuggled in by Prisoners!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Contains " . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "4 $rarity Rarity" . TextFormat::GRAY . " items...";
        $rewards = Nexus::getInstance()->getGameManager()->getRewardsManager()->getContraband($rarity)->getRewards();
        foreach($rewards as $reward) {
            $lore[] = TextFormat::RESET . TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$rarity] . " * " . TextFormat::RESET . TextFormat::GRAY . TextFormat::clean($reward->getName());
        }
        $this->rarity = $rarity;
        parent::__construct(VanillaBlocks::ENDER_CHEST()->asItem(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::CONTRABAND => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $rarity = $tag->getString(self::CONTRABAND);
        $uuid = $tag->getString(self::UUID);
        return new Contraband($rarity, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::CONTRABAND, $this->rarity);
        $tag->setString(self::UUID, $this->getUniqueId());
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
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        $slots = $player->getInventory()->getSize() - count($player->getInventory()->getContents());
        if($slots < 4) {
            $player->sendTranslatedMessage("fullInventory");
            return;
        }
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $rarity = $this->getRarity();
        $rewards = Nexus::getInstance()->getGameManager()->getRewardsManager()->getContraband($rarity);
        $player->getCore()->getScheduler()->scheduleRepeatingTask(new ContrabandAnimationTask($player, $rarity, $rewards), 1);
        $event = new OpenItemEvent($player, $this);
        $event->call();
    }
}