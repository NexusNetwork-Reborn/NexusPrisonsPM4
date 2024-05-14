<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\game\item\types\custom\SkinScroll;
use libs\form\FormIcon;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\utils\TextFormat;

class ItemSkinInfoForm extends MenuForm {

    public function __construct(SkinScroll $item) {
        $title = TextFormat::BOLD . TextFormat::AQUA . $item->getSkinName();
        $elements = [];
        $elements[] = new MenuOption("Close", new FormIcon(FormIcon::IMAGE_TYPE_PATH, "textures/items/" . $item->getSkinId()));
        parent::__construct($title, "", $elements);
    }
}