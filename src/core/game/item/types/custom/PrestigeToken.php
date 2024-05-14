<?php

declare(strict_types=1);

namespace core\game\item\types\custom;

use core\command\inventory\PrestigeTokenInventory;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\Interactive;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\utils\TextFormat;

class PrestigeToken extends Interactive {

    const PRESTIGE = "Prestige";

    /** @var int */
    private $prestige;

    /**
     * PrestigeToken constructor.
     *
     * @param int $prestige
     * @param string|null $uuid
     */
    public function __construct(int $prestige, ?string $uuid = null) {
        $prestige = min(10, $prestige);
        $prestige = max(0, $prestige);
        $this->prestige = $prestige;
        $customName = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "Pickaxe Prestige Token " . TextFormat::WHITE . EnchantmentManager::getRomanNumber($prestige);
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Grants ONE free Prestige";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "for any pickaxe, without the requirements!";
        $lore[] = TextFormat::RESET . TextFormat::RED . "This item can prestige your pickaxe";
        $lore[] = TextFormat::RESET . TextFormat::RED . "up to a maximum prestige of: $prestige";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right-click to activate this";
        parent::__construct(VanillaBlocks::SUNFLOWER()->asItem(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::PRESTIGE => ShortTag::class
        ];
    }

    /**
     * @return int
     */
    public function getPrestige(): int {
        return $this->prestige;
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $uuid = $tag->getString(self::UUID);
        $prestige = $tag->getShort(self::PRESTIGE);
        return new self($prestige, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setShort(self::PRESTIGE, $this->prestige);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        if(!Nexus::getInstance()->getServerManager()->getAreaManager()->canDamage($player->getPosition())) {
            $player->sendDelayedWindow(new PrestigeTokenInventory($player, $this));
            $inventory->setItemInHand($item->setCount($item->getCount() - 1));
            return;
        }
        $player->sendTranslatedMessage("inWarzone");
        $player->playErrorSound();
    }
}