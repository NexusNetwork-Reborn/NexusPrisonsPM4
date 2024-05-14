<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Fireworks;
use core\level\entity\types\FireworksRocket;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use pocketmine\block\Block;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;;

use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class SpaceVisor extends Interactive {

    const SPACE_VISOR = "SpaceVisor";

    const USER = "User";

    /** @var string */
    private $user;

    /**
     * SpaceVisor constructor.
     *
     * @param string $user
     */
    public function __construct(string $user) {
        $this->user = $user;
        $colors = DyeColor::getAll();
        $color = $colors[array_rand($colors)];
        $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Space Visor";
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "A cool space helmet!";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Tailored for " . TextFormat::BLUE . $user;
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right-click to equip!";
        parent::__construct(VanillaBlocks::STAINED_GLASS()->setColor($color)->asItem(), $customName, $lore, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::SPACE_VISOR => StringTag::class,
            self::USER => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $user = $tag->getString(self::USER);
        return new self($user);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::SPACE_VISOR, self::SPACE_VISOR);
        $tag->setString(self::USER, $this->user);
        return $tag;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        $copy = $player->getArmorInventory()->getHelmet();
        $player->getArmorInventory()->setHelmet($item);
        $player->getInventory()->setItemInHand($copy);
    }
}