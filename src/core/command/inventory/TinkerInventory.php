<?php

namespace core\command\inventory;

use core\game\item\enchantment\Enchantment;
use core\game\item\event\TinkerEquipmentEvent;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\EnchantmentDust;
use core\game\item\types\custom\EnchantmentOrb;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\Satchel;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\Nexus;
use core\player\NexusPlayer;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class TinkerInventory extends InvMenu {

    const RARITY_TO_ENERGY = [
        Enchantment::SIMPLE => 427,
        Enchantment::UNCOMMON => 926,
        Enchantment::ELITE => 2471,
        Enchantment::ULTIMATE => 4368,
        Enchantment::LEGENDARY => 7638,
        Enchantment::GODLY => 14872,
        Enchantment::EXECUTIVE => 99284,
        Enchantment::ENERGY => 99284
    ];

    /** @var Item[] */
    private $otherItems = [];

    /**
     * TinkerInventory constructor.
     */
    public function __construct() {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Tinker");
        $this->setListener(function(InvMenuTransaction $transaction): InvMenuTransactionResult {
            $slot = $transaction->getAction()->getSlot();
            $player = $transaction->getPlayer();
            $itemClicked = $transaction->getItemClicked();
            $itemClickedWith = $transaction->getItemClickedWith();
            if(!$player instanceof NexusPlayer) {
                return $transaction->discard();
            }
            $energy = $this->recalculateEnergy();
            if($slot === 4) {
                if($energy > 0 or (!empty($this->otherItems))) {
                    $this->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {
                    });
                    $items = $this->getTinkeredItems();
                    foreach($items as $item) {
                        $ev = new TinkerEquipmentEvent($player, $item);
                        $ev->call();
                    }
                    $player->getInventory()->addItem((new Energy($energy))->toItem());
                    foreach($this->otherItems as $otherItem) {
                        if($player->getInventory()->canAddItem($otherItem)) {
                            $player->getInventory()->addItem($otherItem);
                        }
                        else {
                            if($otherItem->getCount() > 64) {
                                $otherItem->setCount(64);
                            }
                            $player->getWorld()->dropItem($player->getPosition(), $otherItem);
                        }
                    }
                    $player->removeCurrentWindow();
                    $transaction->getAction()->getInventory()->clearAll();
                    $player->playDingSound();
                    return $transaction->discard();
                }
                else {
                    return $transaction->discard();
                }
            }
            if(Satchel::isInstanceOf($itemClicked) or Satchel::isInstanceOf($itemClickedWith)) {
                $player->playOrbSound();
                $this->scheduleDelayedRecalculation();
                return $transaction->continue();
            }
            if(EnchantmentBook::isInstanceOf($itemClicked) or EnchantmentBook::isInstanceOf($itemClickedWith)) {
                $player->playOrbSound();
                $this->scheduleDelayedRecalculation();
                return $transaction->continue();
            }
            if(EnchantmentOrb::isInstanceOf($itemClicked) or EnchantmentOrb::isInstanceOf($itemClickedWith)) {
                $player->playOrbSound();
                $this->scheduleDelayedRecalculation();
                return $transaction->continue();
            }
            if($itemClicked instanceof Armor or $itemClicked instanceof Bow or $itemClicked instanceof Sword or $itemClicked instanceof Axe or $itemClicked instanceof Pickaxe) {
                $player->playOrbSound();
                $this->scheduleDelayedRecalculation();
                return $transaction->continue();
            }
            if($itemClickedWith instanceof Armor or $itemClickedWith instanceof Bow or $itemClickedWith instanceof Sword or $itemClickedWith instanceof Axe or $itemClickedWith instanceof Pickaxe) {
                $player->playOrbSound();
                $this->scheduleDelayedRecalculation();
                return $transaction->continue();
            }
            $player->playErrorSound();
            return $transaction->discard();
        });
        $this->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {
            $items = [];
            for($i = 9; $i < 54; $i++) {
                $item = $inventory->getItem($i);
                if($item instanceof Armor or $item instanceof Bow or $item instanceof Sword or $item instanceof Axe or $item instanceof Pickaxe) {
                    $items[] = $item;
                }
                if(Satchel::isInstanceOf($item)) {
                    $items[] = $item;
                }
                if(EnchantmentBook::isInstanceOf($item)) {
                    $items[] = $item;
                }
                if(EnchantmentOrb::isInstanceOf($item)) {
                    $items[] = $item;
                }
            }
            foreach($items as $item) {
                if($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                }
                else {
                    if($item->getCount() > 64) {
                        $item->setCount(64);
                    }
                    $player->getWorld()->dropItem($player->getPosition(), $item);
                }
            }
        });
    }

    /**
     * @return Item[]
     */
    public function getTinkeredItems(): array {
        $items = [];
        for($i = 9; $i < 54; $i++) {
            $item = $this->getInventory()->getItem($i);
            if($item instanceof Armor or $item instanceof Bow or $item instanceof Sword or $item instanceof Axe or $item instanceof Pickaxe) {
                $items[] = $item;
            }
            if(Satchel::isInstanceOf($item)) {
                $items[] = $item;
            }
            if(EnchantmentBook::isInstanceOf($item)) {
                $items[] = $item;
            }
            if(EnchantmentOrb::isInstanceOf($item)) {
                $items[] = $item;
            }
        }
        return $items;
    }

    public function scheduleDelayedRecalculation(): void {
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($this) extends Task {

            /** @var TinkerInventory */
            private $inventory;

            /**
             *  constructor.
             *
             * @param TinkerInventory $inventory
             */
            public function __construct(TinkerInventory $inventory) {
                $this->inventory = $inventory;
            }

            /**
             * @param int $currentTick
             */
            public function onRun(): void {
                if(count($this->inventory->getInventory()->getViewers()) > 0) {
                    $this->inventory->recalculateEnergy();
                }
            }
        }, 1);
    }

    /**
     * @return int
     */
    public function recalculateEnergy(): int {
        $items = $this->getTinkeredItems();
        if(empty($items)) {
            $display = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 14, 1);
            $display->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "(!) NOTHING OFFERED");
            $lore = [];
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "Offer pickaxes/gear/enchants from your";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "inventory to see what the Tinkerer can";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "transform them into!";
            $display->setLore($lore);
            $this->getInventory()->setItem(4, $display);
            return 0;
        }
        $energy = 0;
        $this->otherItems = [];
        foreach($items as $item) {
            if($item instanceof Pickaxe) {
                $energy += ($item->getEnergy() * 0.02);
            }
            if(Satchel::isInstanceOf($item)) {
                $satchel = Satchel::fromItem($item);
                $energy += ($satchel->getEnergy() * 0.02);
            }
            if(EnchantmentOrb::isInstanceOf($item)) {
                $orb = EnchantmentOrb::fromItem($item);
                $enchantment = $orb->getEnchantment();
                $this->otherItems[] = (new EnchantmentDust(($enchantment->getType()->getRarity() + 1) * $enchantment->getLevel()))->toItem()->setCount(1);
            }
            if(EnchantmentBook::isInstanceOf($item)) {
                $book = EnchantmentBook::fromItem($item);
                $energy += ($book->getEnergy() * 0.95);
                $enchantment = $book->getEnchantment();
                $energy += ($enchantment->getLevel() * (self::RARITY_TO_ENERGY[$enchantment->getType()->getRarity()] ?? 0));
                continue;
            }
            /** @var EnchantmentInstance $enchantment */
            foreach($item->getEnchantments() as $enchantment) {
                $energy += ($enchantment->getLevel() * (self::RARITY_TO_ENERGY[$enchantment->getType()->getRarity()] ?? 0));
            }
        }
        $energy = (int)floor($energy);
        $display = ItemFactory::getInstance()->get(ItemIds::DYE, 12, 1);
        $display->setCustomName(TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "(!) ACCEPT");
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Tinkerer will transform offer info:";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . " * " . TextFormat::WHITE . number_format($energy) . TextFormat::AQUA . " Energy";
        foreach($this->otherItems as $otherItem) {
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . " * " . TextFormat::RESET . $otherItem->getCustomName();
        }
        $lore[] = TextFormat::RESET . TextFormat::RED . "-------" . TextFormat::BOLD . "WARNING" . TextFormat::RESET . TextFormat::RED . "-------";
        $lore[] = TextFormat::RESET . TextFormat::RED . "ALL items offered will be LOST forever!";
        $display->setLore($lore);
        $this->getInventory()->setItem(4, $display);
        return $energy;
    }

    public function initItems(): void {
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 15, 1);
        $glass->setCustomName(" ");
        for($i = 0; $i < 9; $i++) {
            $this->getInventory()->setItem($i, $glass);
        }
        $display = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 14, 1);
        $display->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "(!) NOTHING OFFERED");
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Offer pickaxes/gear/enchants from your";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "inventory to see what the Tinkerer can";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "transform them into!";
        $display->setLore($lore);
        $this->getInventory()->setItem(4, $display);
    }
}