<?php

namespace core\command\inventory;

use core\command\forms\TransactionForm;
use core\game\economy\EconomyCategory;
use core\game\economy\EconomyException;
use core\game\economy\event\ItemSellEvent;
use core\game\economy\PriceEntry;
use core\game\item\mask\Mask;
use core\game\item\types\custom\Satchel;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class ShopListInventory extends InvMenu {

    /** @var EconomyCategory */
    private $place;

    /** @var PriceEntry */
    private $entries;

    /**
     * ShopListInventory constructor.
     *
     * @param EconomyCategory $place
     */
    public function __construct(EconomyCategory $place) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->initItems($place);
        $this->place = $place;
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . $place->getName());
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if(isset($this->entries[$slot])) {
                /** @var PriceEntry $entry */
                $entry = $this->entries[$slot];
                if($entry->getLevel() > $player->getDataSession()->getTotalXPLevel() and $player->getDataSession()->getPrestige() <= 0) {
                    $player->sendMessage(Translation::getMessage("noPermission"));
                    $player->playErrorSound();
                    return;
                }
                if($entry->getBuyPrice() === null and $entry->getSellPrice() !== null) {
                    $sell = null;
                    foreach($player->getInventory()->getContents() as $slot => $item) {
                        if(Satchel::isInstanceOf($item)) {
                            $satchel = Satchel::fromItem($item);
                            $amount = $satchel->getAmount();
                            $item = $satchel->getType()->setCount($amount);
                            if($entry->equal($item)) {
                                $satchel->setAmount(0);
                                $player->getInventory()->setItem($slot, $satchel->toItem());
                            }
                        }
                        if(!$entry->equal($item)) {
                            continue;
                        }
                        if($sell === null) {
                            $sell = $item;
                        }
                        else {
                            $sell->setCount($sell->getCount() + $item->getCount());
                        }
                    }
                    if($sell === null or $sell->getCount() === 0) {
                        $player->playErrorSound();
                        return;
                    }
                    $price = $sell->getCount() * $entry->getSellPrice();
                    $player->removeItemExact($sell);
                    $helmet = $player->getArmorInventory()->getHelmet();
                    if($helmet instanceof Armor) {
                        if($helmet->hasMask(Mask::GUARD)) {
                            $price *= 1.1;
                        }
                    }
                    $event = new ItemSellEvent($player, $sell, $price);
                    $event->call();
                    $player->sendMessage(Translation::getMessage("sell", [
                        "amount" => TextFormat::AQUA . number_format($sell->getCount()),
                        "item" => TextFormat::AQUA . $entry->getName(),
                        "price" => TextFormat::LIGHT_PURPLE . "$" . number_format($sell->getCount() * $entry->getSellPrice(), 2)
                    ]));
                    $player->getDataSession()->addToBalance($price);
                    $player->playDingSound();
                    return;
                }
                $player->removeCurrentWindow();
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entry, $this->place, $player) extends Task {

                    /** @var PriceEntry */
                    private $entry;

                    /** @var EconomyCategory */
                    private $place;

                    /** @var NexusPlayer */
                    private $player;

                    /**
                     *  constructor.
                     *
                     * @param PriceEntry $entry
                     * @param EconomyCategory $place
                     * @param NexusPlayer $player
                     */
                    public function __construct(PriceEntry $entry, EconomyCategory $place, NexusPlayer $player) {
                        $this->entry = $entry;
                        $this->place = $place;
                        $this->player = $player;
                    }

                    public function onRun(): void {
                        if($this->player->isOnline() and (!$this->player->isClosed())) {
                            $this->player->sendForm(new TransactionForm($this->player, $this->place, $this->entry));
                        }
                    }
                }, 20);
            }
        }));
    }

    /**
     * @param EconomyCategory $place
     */
    public function initItems(EconomyCategory $place): void {
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 14, 1);
        $glass->setCustomName(" ");
        $entries = $place->getEntries();
        foreach($entries as $index => $entry) {
            $slot = $entry->getPlace();
            if($slot !== null) {
                if(!$this->getInventory()->isSlotEmpty($slot)) {
                    throw new EconomyException($entry->getName() . "'s slot number is not available!");
                }
                unset($entries[$index]);
                $display = clone $entry->getItem();
                $this->entries[$slot] = $entry;
                $lore = $display->getLore();
                $add = [];
                $add[] = "";
                if($entry->getBuyPrice() !== null) {
                    $add[] = TextFormat::RESET . TextFormat::GRAY . "Buy Price(ea): " . TextFormat::GREEN . "$" . number_format($entry->getBuyPrice(), 2);
                }
                else {
                    $add[] = TextFormat::RESET . TextFormat::GRAY . "Buy Price(ea): " . TextFormat::RED . "Not buyable";
                }
                if($entry->getSellPrice() !== null) {
                    $add[] = TextFormat::RESET . TextFormat::GRAY . "Sell Price(ea): " . TextFormat::GREEN . "$" . number_format($entry->getSellPrice(), 2);
                }
                else {
                    $add[] = TextFormat::RESET . TextFormat::GRAY . "Sell Price(ea): " . TextFormat::RED . "Not sellable";
                }
                if($display instanceof Armor or $display instanceof Pickaxe or $display instanceof Axe or $display instanceof Bow or $display instanceof Sword) {
                    $display->setOriginalLore($add);
                }
                else {
                    $display->setLore(array_merge($lore, $add));
                }
                $this->getInventory()->setItem($slot, $display);
            }
        }
        for($i = 0; $i < 54; $i++) {
            if($this->getInventory()->isSlotEmpty($i)) {
                $entry = array_shift($entries);
                if($entry instanceof PriceEntry) {
                    $display = clone $entry->getItem();
                    $this->entries[$i] = $entry;
                    $lore = $display->getLore();
                    $add = [];
                    $add[] = "";
                    if($entry->getBuyPrice() !== null) {
                        $add[] = TextFormat::RESET . TextFormat::GRAY . "Buy Price(ea): " . TextFormat::GREEN . "$" . number_format($entry->getBuyPrice(), 2);
                    }
                    else {
                        $add[] = TextFormat::RESET . TextFormat::GRAY . "Buy Price(ea): " . TextFormat::RED . "Not buyable";
                    }
                    if($entry->getSellPrice() !== null) {
                        $add[] = TextFormat::RESET . TextFormat::GRAY . "Sell Price(ea): " . TextFormat::GREEN . "$" . number_format($entry->getSellPrice(), 2);
                    }
                    else {
                        $add[] = TextFormat::RESET . TextFormat::GRAY . "Sell Price(ea): " . TextFormat::RED . "Not sellable";
                    }
                    if($display instanceof Armor or $display instanceof Pickaxe or $display instanceof Axe or $display instanceof Bow or $display instanceof Sword) {
                        $display->setOriginalLore($add);
                    }
                    else {
                        $display->setLore(array_merge($lore, $add));
                    }
                    $this->getInventory()->setItem($i, $display);
                }
            }
        }
    }
}