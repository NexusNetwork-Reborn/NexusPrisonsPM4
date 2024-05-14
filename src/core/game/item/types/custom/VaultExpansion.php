<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\player\NexusPlayer;
use core\player\rank\RankManager;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class VaultExpansion extends Interactive {

    const VAULT_EXPANSION = "VaultExpansion";

    /**
     * VaultExpansion constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::DARK_PURPLE . TextFormat::BOLD . "Vault Expansion";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Unlock +1 vault";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right-click to permanently";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "apply this to your character.";
        parent::__construct(ItemFactory::getInstance()->get(ItemIds::ENDER_EYE), $customName, $lore, true);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::VAULT_EXPANSION => StringTag::class,
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
        $tag->setString(self::VAULT_EXPANSION, self::VAULT_EXPANSION);
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
        $vaults = $session->getRank()->getVaultsLimit($player);
        if($vaults >= RankManager::MAX_VAULTS) {
            $player->sendAlert(Translation::getMessage("max"));
            $player->playErrorSound();
            return;
        }
        $session->addAdditionalVaults();
        $player->playOrbSound();
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
   }
}