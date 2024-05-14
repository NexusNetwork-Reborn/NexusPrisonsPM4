<?php

namespace core\game\boop\command\forms;

use core\game\boop\PunishmentEntry;
use libs\form\CustomForm;
use libs\form\element\Label;
use libs\utils\Utils;
use pocketmine\utils\TextFormat;

class PunishInfoForm extends CustomForm {

    /**
     * PunishInfoForm constructor.
     *
     * @param PunishmentEntry $entry
     */
    public function __construct(PunishmentEntry $entry) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Punish";
        $elements = [];
        $elements[] = new Label("Victim", "Victim: " . $entry->getUsername());
        $elements[] = new Label("Effector", "Effector: " . $entry->getEffector());
        $elements[] = new Label("Reason", "Reason: " . $entry->getReason());
        $elements[] = new Label("Duration", "Duration: " . Utils::secondsToTime($entry->getExpiration()));
        $elements[] = new Label("Date", "Date: " . date("n/j/Y (G:i:s)", $entry->getTime()));
        parent::__construct($title, $elements);
    }
}