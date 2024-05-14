<?php

namespace core\command\inventory;

use core\game\item\ItemManager;
use core\game\item\types\custom\SkinScroll;
use core\player\NexusPlayer;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ItemSkinListInventory extends InvMenu
{

    private const BACK = 45, NEXT = 53;

    private static array $skinData = [];

    private int $chunk = 0;

    private bool $switch;

    private NexusPlayer $player;

    /**
     * BossInventory constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player, string $type, bool $switch = false)
    {
        $this->player = $player;
        $this->switch = $switch;
        if (!isset(self::$skinData[$type])) {
            self::$skinData[$type] = array_chunk(ItemManager::getItemSkins($type), 45);
        }
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->initItems($type);
        $this->setName(TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Item Skins");
        $this->setListener(self::readonly(function (DeterministicInvMenuTransaction $transaction) use ($type, $player): void {
            $slot = $transaction->getAction()->getSlot();
            $item = $transaction->getOut();
            if ($slot === self::BACK && $item->getId() === ItemIds::WOOL && $this->chunk > 0) {
                $this->chunk--;
                $this->initItems($type);
            } elseif ($slot === self::NEXT && $item->getId() === ItemIds::WOOL && isset(self::$skinData[$type][$this->chunk + 1])) {
                $this->chunk++;
                $this->initItems($type);
            } elseif($this->switch && $item->getId() !== ItemIds::BARRIER && isset(self::$skinData[$type][$this->chunk][$slot])) {
                $info = self::$skinData[$type][$this->chunk][$slot];
                $this->player->getDataSession()->setCurrentItemSkin($info[0], $info[2]);
                $this->onClose($this->player);
                $player->playTwinkleSound();
            } elseif($this->switch && $item->getId() === ItemIds::BARRIER) {
                $player->playErrorSound();
            }
        }));
        $this->setInventoryCloseListener(function (Player $player, Inventory $inventory): void {});
    }

    /**
     * @param NexusPlayer $player
     */
    public function initItems(string $type): void
    {
        for($i = 0; $i < 45; $i++) {
            $this->inventory->setItem($i, VanillaItems::AIR());
        }
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 15, 1);
        $glass->setCustomName(" ");
        if ($this->chunk > 0) {
            $this->getInventory()->setItem(self::BACK, VanillaBlocks::WOOL()->setColor(DyeColor::RED())->asItem()->setCustomName(TextFormat::BOLD . TextFormat::RED . "<< LAST PAGE"));
        } else {
            $this->getInventory()->setItem(self::BACK, $glass);
        }

        if (isset(self::$skinData[$type][$this->chunk + 1])) {
            $this->getInventory()->setItem(self::NEXT, VanillaBlocks::WOOL()->setColor(DyeColor::LIME())->asItem()->setCustomName(TextFormat::BOLD . TextFormat::GREEN . "NEXT PAGE >>"));
        } else {
            $this->getInventory()->setItem(self::NEXT, $glass);
        }

        $skins = self::$skinData[$type][$this->chunk];
        for ($i = 0; $i < 45; $i++) { // Fits 44 skins
            if(!isset($skins[$i])) {
                //$this->getInventory()->setItem($i, $glass);
                break;
            }
            $info = $skins[$i]; // 0 -> id, 1 -> name, 2 -> type, 3 -> rarity
            $unlocked = $this->player->getDataSession()->hasItemSkin($info[0]);
            if($this->switch && !$unlocked) {
                $this->getInventory()->setItem($i, VanillaBlocks::BARRIER()->asItem()->setCustomName($info[1]));
                continue;
            }
            $scroll = new SkinScroll($info[0], $info[2], $info[1], $info[3]);
            if ($type === "sword") {
                $fancy = ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD, 0, 1);
            } elseif ($type === "pickaxe") {
                $fancy = ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE, 0, 1);
            }
            $fancy = $scroll->makeNewItem($fancy)->setCustomName($info[1]);
            $fancy->setLore([
                "",
                TextFormat::RESET . TextFormat::AQUA . "Rarity: " . TextFormat::RESET . $info[3],
                TextFormat::RESET . TextFormat::GOLD . "Status: " . ($unlocked ? TextFormat::GREEN . "UNLOCKED" : TextFormat::RED . "LOCKED")
            ]);
            $this->getInventory()->setItem($i, $fancy);
        }
//        for($i = 46; $i < 53; $i++) {
//            $this->getInventory()->setItem($i, $glass);
//        }
    }
}