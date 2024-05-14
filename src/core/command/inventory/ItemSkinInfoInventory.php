<?php

namespace core\command\inventory;

use core\command\task\TeleportTask;
use core\game\boss\BossFight;
use core\game\boss\task\BossSummonTask;
use core\game\item\ItemManager;
use core\game\item\types\custom\SkinScroll;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\game\kit\Kit;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\BlockToolType;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\ToolTier;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class ItemSkinInfoInventory extends InvMenu {

    /**
     * BossInventory constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(SkinScroll $item) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_HOPPER));
        $this->initItems($item);
        $this->setName(TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Item Skin Info");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {}));
        $this->setInventoryCloseListener(function(Player $player, Inventory $inventory) : void {});
    }

    /**
     * @param NexusPlayer $player
     * @param Kit $kit
     */
    public function initItems(SkinScroll $item): void {
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 15, 1);
        $glass->setCustomName(" ");
        for($i = 0; $i < 5; $i++) {
            if($i === 2) {
                if($item->getToolType() === BlockToolType::SWORD) {
                    $fancy = ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD, 0, 1);
                } else if ($item->getToolType() === BlockToolType::PICKAXE) {
                    $fancy = ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE, 0, 1);
                }
                if(isset($fancy)) {
                    $fancy = $item->makeNewItem($fancy)->setCustomName($item->getFancyName());
                    $fancy->setLore([
                        "",
                        TextFormat::RESET . TextFormat::AQUA . "Rarity: " . TextFormat::RESET . Rarity::RARITY_TO_COLOR_MAP[$item->getRarity()] . $item->getRarity()
                    ]);
                    $this->getInventory()->setItem($i, $fancy);
                }
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
    }
}