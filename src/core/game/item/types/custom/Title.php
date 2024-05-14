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

class Title extends Interactive {

    const TITLE = "Title";

    /** @var string */
    private $title;

    /**
     * Title constructor.
     *
     * @param string $title
     */
    public function __construct(string $title) {
        $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Title " . TextFormat::RESET . TextFormat::GOLD . $title;
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right click to claim!";
        $this->title = $title;
        parent::__construct(VanillaItems::PAPER(), $customName, $lore, false);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::TITLE => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $title = $tag->getString(self::TITLE);
        return new self($title);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::TITLE, $this->title);
        return $tag;
    }

    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
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
        if($player->getDataSession()->hasTag($this->title)) {
            $player->sendMessage(Translation::getMessage("alreadyHaveTitle"));
            $player->playErrorSound();
            return;
        }
        $player->sendMessage(Translation::getMessage("claimTitle", [
            "name" => $this->title
        ]));
        $player->playDingSound();
        $player->getDataSession()->addTag($this->title);
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
    }
}