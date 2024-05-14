<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\game\auction\inventory\AuctionListInventory;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Input;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ShowcaseMoveItemForm extends CustomForm {

    /** @var Item */
    private $item;

    /** @var int */
    private $slot;

    /**
     * ShowcaseMoveItemForm constructor.
     *
     * @param Item $item
     * @param int $slot
     */
    public function __construct(Item $item, int $slot) {
        $this->item = $item;
        $this->slot = $slot;
        $title = TextFormat::BOLD . TextFormat::AQUA . "Move Item";
        $elements[] = new Input("Slot", "Enter slot number (0-53)");
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
        $slot = $data->getString("Slot");
        if(!is_numeric($slot)) {
            $player->sendMessage(Translation::getMessage("notNumeric"));
            return;
        }
        $slot = (int)$slot;
        if($slot < 0 or $slot > 53) {
            $player->sendMessage(Translation::getMessage("invalidAmount"));
            return;
        }
        $showcase = $player->getDataSession()->getShowcase();
        if(!$showcase->getInventory()->getItem($slot)->isNull()) {
            $player->sendMessage(Translation::getMessage("replacingFilledSlot"));
            return;
        }
        $showcase->getInventory()->setItem($this->slot, VanillaBlocks::AIR()->asItem());
        $showcase->getInventory()->setItem($slot, $this->item);
    }
}