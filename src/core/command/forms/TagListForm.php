<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class TagListForm extends MenuForm {

    /**
     * TagListForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Titles";
        $text = "Select a title.";
        $tags = $player->getDataSession()->getTags();
        $options = [];
        foreach($tags as $tag) {
            $options[] = new MenuOption($tag . "\n" . TextFormat::RESET . TextFormat::GRAY . "(Click to equip)");
        }
        parent::__construct($title, $text, $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $tag = explode("\n", $this->getOption($selectedOption)->getText())[0];
        $player->getDataSession()->setCurrentTag($tag);
        $player->sendMessage(Translation::getMessage("tagSetSuccess", [
            "tag" => $tag
        ]));
        return;
    }
}