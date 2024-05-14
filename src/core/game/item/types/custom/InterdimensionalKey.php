<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\command\inventory\WarpMenuInventory;
use core\game\item\types\Interactive;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class InterdimensionalKey extends Interactive {

    const INTERDIMENSIONAL_KEY = "InterdimensionalKey";

    /**
     * Absorber constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Interdimensional Key";
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "The key quivers in your hand.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Certainly this must be valuable, important...";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "but how to get to the end of the mine?";
        parent::__construct(VanillaBlocks::TRIPWIRE_HOOK()->asItem(), $customName, $lore, true, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::INTERDIMENSIONAL_KEY => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        return new self();
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::INTERDIMENSIONAL_KEY, self::INTERDIMENSIONAL_KEY);
        return $tag;
    }

    public function onInteract(NexusPlayer $player, Inventory $inventory, Item $item, int $face, Block $block): void
    {
        $task = WarpMenuInventory::getExecutiveSession($player);
        $exec = Nexus::getInstance()->getGameManager()->getWormholeManager()->getExecutiveWormhole();
        if($task === null) {
            $player->sendMessage(TextFormat::RED . "You cannot use the Interdimensional Key outside of the Executive Mine!");
        } elseif($task->isWormholeUnlocked()) {
            $player->sendMessage(TextFormat::RED . "You have already unlocked the Executive Wormhole in this session!");
        } elseif(!$exec->canUse($player, $item, false, false)) {
            $player->sendMessage(TextFormat::RED . "You have to be next to the Executive Wormhole to use an Interdimensional Key!");
        } else {
            $inventory->setItemInHand($item->setCount($item->getCount() - 1));
            $task->unlockWormhole();
            $player->sendMessage(TextFormat::LIGHT_PURPLE . "You have unlocked the Executive Wormhole for your current session, make use of it before your time expires!");
        }
    }
}