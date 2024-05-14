<?php

declare(strict_types = 1);

namespace core\command\forms;

use libs\form\CustomForm;
use libs\form\element\Label;
use pocketmine\utils\TextFormat;

class ChangeLogForm extends CustomForm {

    /**
     * ChangeLogForm constructor.
     */
    public function __construct() {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Change Log";
        $elements = [];
        $elements[] = new Label("Changes",  "Greetings adventurers! The arrival of season 7 has finally come! We are bringing upon many exciting changes for you!\n \n - Tier system for god kits\n - Vote points\n - A faction vault(/f vault) which are accessible to members and up!\n - Generator and spawner limits have been removed\n - Revision to appearances of UIs(such as the kits UI and etc)\n - More custom enchantments\n - Bounty\n - A pass full of quests(/pass)\n - Improved anti-cheat\n - More bug fixes\n \nEnjoy season 7!");
        parent::__construct($title, $elements);
    }
}