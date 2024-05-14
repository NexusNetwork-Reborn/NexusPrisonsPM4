<?php

namespace core\command\inventory;

use core\game\item\ItemManager;
use core\game\item\types\custom\SkinScroll;
use core\player\NexusPlayer;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\BlockToolType;
use pocketmine\inventory\Inventory;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ItemSkinListSelectionInventory extends InvMenu
{

    private bool $switch;

    private NexusPlayer $player;

    /**
     * BossInventory constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player, bool $switch = false)
    {
        $this->switch = $switch;
        $this->player = $player;
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_HOPPER));
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Item Skins");
        $this->setListener(self::readonly(function (DeterministicInvMenuTransaction $transaction) use ($player, $switch): void {
            if ($transaction->getAction()->getSlot() === 1) {
                $player->sendDelayedWindow((new ItemSkinListInventory($player, "sword", $switch)));
            } elseif ($transaction->getAction()->getSlot() === 3) {
                $player->sendDelayedWindow((new ItemSkinListInventory($player, "pickaxe", $switch)));
            }
        }));
        $this->setInventoryCloseListener(function (Player $player, Inventory $inventory): void {
        });
    }

    public function initItems(): void
    {
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 15, 1);
        $glass->setCustomName(" ");
        $defaultSword = ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD, 0, 1)->setCustomName(TextFormat::BOLD . TextFormat::RED . "Sword Skins");
        $defaultPickaxe = ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE, 0, 1)->setCustomName(TextFormat::BOLD . TextFormat::AQUA . "Pickaxe Skins");
        if ($this->switch) {
            $current = $this->player->getDataSession()->getCurrentItemSkin();

            $sword = (strlen($current[0]) > 0 ? $current[0] : null);
            if ($sword !== null) $swordScroll = ItemManager::getSkinScroll($current[0]);
            $pickaxe = (strlen($current[1]) > 0 ? $current[1] : null);
            if ($pickaxe !== null) $pickaxeScroll = ItemManager::getSkinScroll($current[1]);

            $defaultSword->setCustomName(TextFormat::BOLD . TextFormat::RED . "Change Sword Skin");
            $defaultSword->setLore([TextFormat::GOLD . "Current: " . TextFormat::YELLOW . (isset($swordScroll) ? $swordScroll->getSkinName() : "N/A")]);
            $defaultPickaxe->setCustomName(TextFormat::BOLD . TextFormat::AQUA . "Change Pickaxe Skin");
            $defaultPickaxe->setLore([TextFormat::GOLD . "Current: " . TextFormat::YELLOW . (isset($pickaxeScroll) ? $pickaxeScroll->getSkinName() : "N/A")]);

            $defaultSword = (isset($swordScroll) ? $swordScroll->makeNewItem($defaultSword) : $defaultSword);
            $defaultPickaxe = (isset($pickaxeScroll) ? $pickaxeScroll->makeNewItem($defaultPickaxe) : $defaultPickaxe);
        }
        for ($i = 0; $i < 5; $i++) {
            if ($i === 1) {
                $this->getInventory()->setItem($i, $defaultSword);
            } elseif ($i === 3) {
                $this->getInventory()->setItem($i, $defaultPickaxe);
            } else {
                $this->getInventory()->setItem($i, $glass);
            }
        }
    }
}