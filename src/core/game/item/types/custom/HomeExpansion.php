<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\player\NexusPlayer;
use core\player\rank\RankManager;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\level\sound\AnvilBreakSound;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class HomeExpansion extends Interactive {

    const HOME_EXPANSION = "HomeExpansion";

    /**
     * HomeExpansion constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Home Expansion";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Unlock +1 home";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right-click to permanently";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "apply this to your character.";
        parent::__construct(VanillaBlocks::BED()->asItem(), $customName, $lore, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::HOME_EXPANSION => StringTag::class,
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
        $tag->setString(self::HOME_EXPANSION, self::HOME_EXPANSION);
        return $tag;
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
        if(!$player->isLoaded()) {
            return;
        }
        $session = $player->getDataSession();
        if($session->getRank()->getHomeLimit($player) >= RankManager::MAX_HOMES) {
            $player->sendAlert(Translation::getMessage("max"));
            $player->playErrorSound();
            return;
        }
        $session->addAdditionalHomes();
        $player->playOrbSound();
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
    }
}