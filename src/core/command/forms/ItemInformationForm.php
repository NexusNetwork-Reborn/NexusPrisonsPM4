<?php

namespace core\command\forms;

use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use libs\form\CustomForm;
use libs\form\element\Label;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class ItemInformationForm extends CustomForm {

    /**
     * ItemInformationForm constructor.
     *
     * @param Item $item
     */
    public function __construct(Item $item) {
        if($item instanceof Pickaxe or $item instanceof Armor or $item instanceof Bow or $item instanceof Sword or $item instanceof Axe) {
            $elements = [];
            $elements[] = new Label("Lore", implode("\n", $item->getLoreForItem()));
            $title = $item->getCustomNameForItem();
        }
        else {
            $title = $item->hasCustomName() ? $item->getCustomName() : TextFormat::RESET . TextFormat::WHITE . $item->getName();
            $elements[] = new Label("Lore", implode("\n", $item->getLore()));
        }
        parent::__construct($title, $elements);
    }
}