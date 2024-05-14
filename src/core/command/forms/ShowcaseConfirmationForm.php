<?php

namespace core\command\forms;

use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\ModalForm;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ShowcaseConfirmationForm extends ModalForm {

    /** @var Item */
    private $item;

    /** @var int */
    private $slot;

    /**
     * ShowcaseDeleteItemForm constructor.
     *
     * @param Item $item
     * @param int $slot
     */
    public function __construct(Item $item, int $slot) {
        $this->item = $item;
        $this->slot = $slot;
        $name = $item->hasCustomName() ? $item->getCustomName() : $item->getName();
        $title = TextFormat::BOLD . TextFormat::AQUA . "Add item";
        $text = "Are you sure you want to add $name" . TextFormat::RESET . " to your showcase? You will be PERMANENTLY putting this item up for display. You will only be able to move this item around or remove it from your showcase.";
        parent::__construct($title, $text);
    }

    /**
     * @param Player $player
     * @param bool $choice
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, bool $choice): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($choice == true) {
            $name = $this->item->hasCustomName() ? $this->item->getCustomName() : $this->item->getName();
            if(!$player->getInventory()->contains($this->item)) {
                $player->sendMessage(Translation::RED . "Could not find the item in your inventory!");
                return;
            }
            $player->getInventory()->removeItem($this->item);
            $player->getDataSession()->getShowcase()->getInventory()->setItem($this->slot, $this->item);
            $player->sendMessage(Translation::GREEN . $name . TextFormat::RESET . TextFormat::GRAY . " has been added to your showcase!");
        }
    }
}