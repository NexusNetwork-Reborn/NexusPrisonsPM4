<?php

namespace core\command\forms;

use core\game\plots\plot\Plot;
use core\game\plots\plot\PlotOwner;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\ModalForm;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ShowcaseDeleteItemForm extends ModalForm {

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
        $title = TextFormat::BOLD . TextFormat::AQUA . "Remove item";
        $text = "Are you sure you want to remove $name" . TextFormat::RESET . " from your showcase?";
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
            $player->getDataSession()->getShowcase()->getInventory()->setItem($this->slot, VanillaBlocks::AIR()->asItem());
            $player->sendMessage(Translation::GREEN . $name . TextFormat::RESET . TextFormat::GRAY . " has been removed from your showcase!");
        }
    }
}