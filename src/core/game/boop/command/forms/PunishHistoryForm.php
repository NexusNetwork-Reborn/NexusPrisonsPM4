<?php

namespace core\game\boop\command\forms;

use core\game\boop\PunishmentEntry;
use libs\form\CustomForm;
use libs\form\element\Label;
use pocketmine\utils\TextFormat;

class PunishHistoryForm extends CustomForm {

    /**
     * PunishListForm constructor.
     *
     * @param PunishmentEntry[][][] $entries
     */
    public function __construct(array $entries) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Punish";
        $elements = [];
        $i = 0;
        foreach($entries as $type => $list) {
            foreach($list as $reason => $anotherList) {
                $elements[] = new Label(++$i, "Violations for \"$reason\": " . count($anotherList));
            }
        }
        parent::__construct($title, $elements);
    }
}