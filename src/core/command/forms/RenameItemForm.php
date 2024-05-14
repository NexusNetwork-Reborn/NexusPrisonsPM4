<?php

namespace core\command\forms;

use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Input;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\AnvilUseSound;

class RenameItemForm extends CustomForm {

    /** @var Item */
    private $item;

    /** @var Item */
    private $nameTag;

    /**
     * RenameItemForm constructor.
     *
     * @param Item $item
     * @param Item $nameTag
     */
    public function __construct(Item $item, Item $nameTag) {
        $this->item = $item;
        $this->nameTag = $nameTag;
        $elements = [];
        $title = TextFormat::BOLD . TextFormat::AQUA . "Rename";
        $text = "What would you like to rename your item?";
        $elements[] = new Input("CustomName", $text);
        parent::__construct($title, $elements);
    }

    /**
     * @param Player $player
     * @param CustomFormResponse $data
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, CustomFormResponse $data): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $inventory = $player->getInventory();
        if((!$inventory->contains($this->item)) or (!$inventory->contains($this->nameTag))) {
            $player->playErrorSound();
            $player->sendTranslatedMessage("itemNotFound");
            return;
        }
        $value = $data->getString("CustomName");
        $name = str_replace("&", TextFormat::ESCAPE, $value);
        if(strlen($name) > 30) {
            $player->sendMessage(Translation::getMessage("nameTooLong"));
            return;
        }
        if($this->item instanceof Pickaxe or $this->item instanceof Armor or $this->item instanceof Bow or $this->item instanceof Sword or $this->item instanceof Axe) {
            $player->getInventory()->remove($this->item);
            $this->item->setOriginalCustomName(TextFormat::RESET . $name);
            $player->getInventory()->addItem($this->item);
            $player->getInventory()->removeItem($this->nameTag->setCount(1));
            $player->sendMessage(Translation::getMessage("successRename"));
            $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound());
            return;
        }
    }
}