<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\player\NexusPlayer;
use libs\form\FormIcon;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ShowcaseActionForm extends MenuForm {

    /** @var Item */
    private $item;

    /** @var int */
    private $slot;

    /**
     * ShowcaseActionForm constructor.
     *
     * @param Item $item
     * @param int $slot
     */
    public function __construct(Item $item, int $slot) {
        $this->item = $item;
        $this->slot = $slot;
        $title = TextFormat::BOLD . TextFormat::AQUA . "Showcase";
        $text = "What would you like to do?";
        $options = [];
        $options[] = new MenuOption("Move Item");
        $options[] = new MenuOption("Delete Item");
        parent::__construct($title, $text, $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $option = $this->getOption($selectedOption);
        $text = $option->getText();
        switch($text) {
            case "Move Item":
                $player->sendForm(new ShowcaseMoveItemForm($this->item, $this->slot));
                break;
            case "Delete Item":
                $player->sendForm(new ShowcaseDeleteItemForm($this->item, $this->slot));
                break;
        }
    }
}