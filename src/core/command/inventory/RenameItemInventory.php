<?php

namespace core\command\inventory;

use core\command\forms\RenameItemForm;
use core\game\item\ItemManager;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\game\kit\Kit;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use core\server\inventory\InventoryTypes;
use core\translation\Translation;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\BlockFactory;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class RenameItemInventory extends InvMenu {

    /** @var Item */
    private $tag;

    /**
     * RenameItemInventory constructor.
     *
     * @param NexusPlayer $player
     * @param Item $tag
     */
    public function __construct(NexusPlayer $player, Item $tag) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InventoryTypes::TYPE_DISPENSER));
        $this->initItems($player);
        $this->tag = $tag;
        $this->setName(TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "Rename Item");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            /** @var NexusPlayer $player */
            $player = $transaction->getPlayer();
            $itemClickWith = $transaction->getItemClickedWith();
            if($slot === 4) {
                if($itemClickWith instanceof Pickaxe or $itemClickWith instanceof Armor or $itemClickWith instanceof Bow or $itemClickWith instanceof Sword or $itemClickWith instanceof Axe) {
                    $player->removeCurrentWindow();
                    $player->sendDelayedForm(new RenameItemForm($itemClickWith, $this->tag));
                }
                else {
                    $player->removeCurrentWindow();
                    $player->playErrorSound();
                    $player->sendTranslatedMessage("invalidItem");
                }
            }
        }));
    }

    /**
     * @param NexusPlayer $player
     * @param Pickaxe $pickaxe
     */
    public function initItems(NexusPlayer $player): void {
        $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::WHITE())->asItem()->setCount(1);
        $glass->setCustomName(TextFormat::RESET . TextFormat::GRAY . "Place a weapon/tool/armor into the empty slot");
        for($i = 0; $i < 9; $i++) {
            if($i === 4) {
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
    }
}