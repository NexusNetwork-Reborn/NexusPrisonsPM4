<?php

namespace core\game\boop\command\forms;

use core\Nexus;
use core\translation\Translation;
use core\game\boop\PunishmentEntry;
use libs\form\FormIcon;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PunishMenuForm extends MenuForm {

    /**
     * PunishMenuForm constructor.
     */
    public function __construct() {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Punish";
        $text = "Select an action.";
        $options = [];
        $options[] = new MenuOption("View bans", new FormIcon("http://www.aethic.games/images/hourglass.png", FormIcon::IMAGE_TYPE_URL));
        $options[] = new MenuOption("View mutes", new FormIcon("http://www.aethic.games/images/hourglass.png", FormIcon::IMAGE_TYPE_URL));
        $options[] = new MenuOption("View blocks", new FormIcon("http://www.aethic.games/images/hourglass.png", FormIcon::IMAGE_TYPE_URL));
        $options[] = new MenuOption("Ban", new FormIcon("http://www.aethic.games/images/flag.png", FormIcon::IMAGE_TYPE_URL));
        $options[] = new MenuOption("Mute", new FormIcon("http://www.aethic.games/images/flag.png", FormIcon::IMAGE_TYPE_URL));
        parent::__construct($title, $text, $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        $option = $this->getOption($selectedOption);
        switch($option->getText()) {
            case "View bans":
                $player->sendForm(new PunishListForm(Nexus::getInstance()->getGameManager()->getBOOPManager()->getBans()));
                break;
            case "View mutes":
                $player->sendForm(new PunishListForm(Nexus::getInstance()->getGameManager()->getBOOPManager()->getMutes()));
                break;
            case "View blocks":
                $player->sendForm(new PunishListForm(Nexus::getInstance()->getGameManager()->getBOOPManager()->getBlocks()));
                break;
            case "Ban":
                if(!$player->hasPermission("permission.mod")) {
                    $player->sendMessage(Translation::getMessage("noPermission"));
                    return;
                }
                $player->sendForm(new PunishActionForm(PunishmentEntry::BAN));
                break;
            case "Mute":
                $player->sendForm(new PunishActionForm(PunishmentEntry::MUTE));
                break;
        }
    }
}