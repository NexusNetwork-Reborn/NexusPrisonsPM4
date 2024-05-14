<?php

namespace core\command\inventory;

use core\command\forms\ItemInformationForm;
use core\game\item\types\vanilla\Pickaxe;
use core\player\NexusPlayer;
use core\server\inventory\InventoryTypes;
use core\translation\Translation;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SeeItemInventory extends InvMenu {

    /** @var Item */
    private $item;

    /**
     * SeeItemInventory constructor.
     *
     * @param Item $item
     */
    public function __construct(Item $item) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InventoryTypes::TYPE_DISPENSER));
        $this->item = $item;
        $this->initItems();
        $this->setName($item->hasCustomName() ? $item->getCustomName() : TextFormat::RESET . TextFormat::WHITE . $item->getName());
        $this->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            /** @var NexusPlayer $player */
            $player = $transaction->getPlayer();
            if($transaction->getAction()->getSlot() === 4) {
                $player->removeCurrentWindow();
                $player->sendDelayedForm(new ItemInformationForm($this->item));
            }
        }));
    }

    public function initItems(): void {
        $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem()->setCount(1);
        $glass->setCustomName(" ");
        for($i = 0; $i < 9; $i++) {
            if($i === 4) {
                $this->getInventory()->setItem($i, $this->item);
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
    }
}