<?php

namespace core\game\boop\command\forms;

use core\translation\Translation;
use core\translation\TranslationException;
use core\game\boop\PunishmentEntry;
use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Input;
use libs\form\element\Label;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PunishListForm extends CustomForm {

    /** @var PunishmentEntry[] */
    private $entries;

    /**
     * PunishListForm constructor.
     *
     * @param PunishmentEntry[] $entries
     */
    public function __construct(array $entries) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Punish";
        $elements = [];
        $this->entries = $entries;
        $list = "";
        foreach($entries as $entry) {
            if(empty($list)) {
                $list .= $entry->getUsername();
            }
            else {
                $list .= ", " . $entry->getUsername();
            }
        }
        $elements[] = new Input("Lookup", "Lookup");
        $elements[] = new Label("List", $list);
        parent::__construct($title, $elements);
    }

    /**
     * @param Player $player
     * @param CustomFormResponse $data
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, CustomFormResponse $data): void {
        $lookup = $data->getString("Lookup");
        if(!isset($this->entries[$lookup])) {
            $player->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $player->sendForm(new PunishInfoForm($this->entries[$lookup]));
    }
}