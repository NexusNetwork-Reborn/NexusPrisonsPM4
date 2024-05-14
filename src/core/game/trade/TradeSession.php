<?php

declare(strict_types = 1);

namespace core\game\trade;

use core\game\item\event\TradeItemEvent;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\TranslationException;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class TradeSession {

    /** @var NexusPlayer */
    private $sender;

    /** @var NexusPlayer */
    private $receiver;

    /** @var int */
    private $time;

    /** @var null|int */
    private $tradeTime = null;

    /** @var bool */
    private $senderStatus = false;

    /** @var bool */
    private $receiverStatus = false;

    /** @var InvMenu */
    private $menu;

    /** @var int */
    private $key;

    /**
     * TradeSession constructor.
     *
     * @param NexusPlayer $sender
     * @param NexusPlayer $receiver
     */
    public function __construct(NexusPlayer $sender, NexusPlayer $receiver) {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->time = time();
        $this->menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $this->menu->setName(TextFormat::YELLOW . "Trading Session");
        $item = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 14);
        $item->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "DENY");
        $item->setLore([TextFormat::RESET . TextFormat::GRAY . "Left Side", TextFormat::RESET . TextFormat::GRAY . "This can only be modified by " . TextFormat::LIGHT_PURPLE . $this->sender->getName()]);
        $this->menu->getInventory()->setItem(4, $item);
        $item = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 14);
        $item->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "DENY");
        $item->setLore([TextFormat::RESET . TextFormat::GRAY . "Right Side", TextFormat::RESET . TextFormat::GRAY . "This can only be modified by " . TextFormat::LIGHT_PURPLE . $this->receiver->getName()]);
        $this->menu->getInventory()->setItem(22, $item);
        $this->menu->setListener(function(InvMenuTransaction $transaction): InvMenuTransactionResult {
            $action = $transaction->getAction();
            $player = $transaction->getPlayer();
            $itemClicked = $transaction->getItemClicked();
            if($action->getSlot() === 4 and $player->getUniqueId()->toString() === $this->sender->getUniqueId()->toString()) {
                if($itemClicked->getId() === ItemIds::STAINED_GLASS_PANE and $itemClicked->getMeta() === 14) {
                    $item = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 13);
                    $item->setCustomName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "ACCEPT");
                    $item->setLore([TextFormat::RESET . TextFormat::GRAY . "This can only be modified by " . TextFormat::LIGHT_PURPLE . $this->sender->getName()]);
                    $action->getInventory()->setItem(4, $item);
                    $this->senderStatus = true;
                    return $transaction->discard();
                }
                elseif($itemClicked->getId() === ItemIds::STAINED_GLASS_PANE and $itemClicked->getMeta() === 13) {
                    $item = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 14);
                    $item->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "DENY");
                    $item->setLore([TextFormat::RESET . TextFormat::GRAY . "This can only be modified by " . TextFormat::LIGHT_PURPLE . $this->sender->getName()]);
                    $action->getInventory()->setItem(4, $item);
                    $this->senderStatus = false;
                    return $transaction->discard();
                }
            }
            if($action->getSlot() === 22 and $player->getUniqueId()->toString() === $this->receiver->getUniqueId()->toString()) {
                if($itemClicked->getId() === ItemIds::STAINED_GLASS_PANE and $itemClicked->getMeta() === 14) {
                    $item = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 13);
                    $item->setCustomName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "ACCEPT");
                    $item->setLore([TextFormat::RESET . TextFormat::GRAY . "This can only be modified by " . TextFormat::LIGHT_PURPLE . $this->receiver->getName()]);
                    $action->getInventory()->setItem(22, $item);
                    $this->receiverStatus = true;
                    return $transaction->discard();
                }
                elseif($itemClicked->getId() === ItemIds::STAINED_GLASS_PANE and $itemClicked->getMeta() === 13) {
                    $item = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 14);
                    $item->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "DENY");
                    $item->setLore([TextFormat::RESET . TextFormat::GRAY . "This can only be modified by " . TextFormat::LIGHT_PURPLE . $this->receiver->getName()]);
                    $action->getInventory()->setItem(22, $item);
                    $this->receiverStatus = false;
                    return $transaction->discard();
                }
            }
            if($action->getSlot() === 13) {
                return $transaction->discard();
            }
            if(($action->getSlot() % 9) < 4 and $player->getUniqueId()->toString() === $this->sender->getUniqueId()->toString() and $this->senderStatus === false and $this->receiverStatus === false) {
                return $transaction->continue();
            }
            if(($action->getSlot() % 9) > 4 and $player->getUniqueId()->toString() === $this->receiver->getUniqueId()->toString() and $this->receiverStatus === false and $this->senderStatus === false) {
                return $transaction->continue();
            }
            return $transaction->discard();
        });
        $this->menu->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {
                $senderInventory = $this->sender->getInventory();
                $receiverInventory = $this->receiver->getInventory();
                $time = 5 - (time() - $this->tradeTime);
                if($this->tradeTime !== null and $time <= 0) {
                    $senderItems = [];
                    foreach($this->menu->getInventory()->getContents() as $slot => $item) {
                        if(($slot % 9) < 4) {
                            $senderItems[] = $item;
                            if($receiverInventory->canAddItem($item)) {
                                $receiverInventory->addItem($item);
                                continue;
                            }
                            $this->receiver->getWorld()->dropItem($this->receiver->getPosition(), $item);
                        }
                    }
                    $ev = new TradeItemEvent($this->sender, $senderItems);
                    $ev->call();
                    $receiverItems = [];
                    foreach($this->menu->getInventory()->getContents() as $slot => $item) {
                        if(($slot % 9) > 4) {
                            $receiverItems[] = $item;
                            if($senderInventory->canAddItem($item)) {
                                $senderInventory->addItem($item);
                                continue;
                            }
                            $this->sender->getWorld()->dropItem($this->sender->getPosition(), $item);
                        }
                    }
                    $ev = new TradeItemEvent($this->receiver, $receiverItems);
                    $ev->call();
                }
                else {
                    foreach($this->menu->getInventory()->getContents() as $slot => $item) {
                        if(($slot % 9) < 4) {
                            if($senderInventory->canAddItem($item)) {
                                $senderInventory->addItem($item);
                                continue;
                            }
                            $this->sender->getWorld()->dropItem($this->sender->getPosition(), $item);
                        }
                    }
                    foreach($this->menu->getInventory()->getContents() as $slot => $item) {
                        if(($slot % 9) > 4) {
                            if($receiverInventory->canAddItem($item)) {
                                $receiverInventory->addItem($item);
                                continue;
                            }
                            $this->receiver->getWorld()->dropItem($this->receiver->getPosition(), $item);
                        }
                    }
                }
                $this->menu->getInventory()->clearAll();
                if($this->key === null) {
                    return;
                }
                Nexus::getInstance()->getGameManager()->getTradeManager()->removeSession($this->key);
            }
        );
    }

    /**
     * @return NexusPlayer
     */
    public function getSender(): NexusPlayer {
        return $this->sender;
    }

    /**
     * @return NexusPlayer
     */
    public function getReceiver(): NexusPlayer {
        return $this->receiver;
    }

    public function sendMenus() {
        $this->menu->send($this->sender);
        $this->menu->send($this->receiver);
    }

    /**
     * @param int $key
     * @param TradeManager $manager
     *
     * @throws TranslationException
     */
    public function tick(int $key, TradeManager $manager): void {
        $this->key = $key;
        foreach($this->menu->getInventory()->getViewers() as $viewer) {
            if($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                $viewer->getNetworkSession()->getInvManager()->syncContents($this->menu->getInventory());
            }
        }
        if($this->senderStatus === true and $this->receiverStatus === true) {
            if($this->tradeTime === null) {
                $this->tradeTime = time();
            }
        }
        else {
            $this->tradeTime = null;
        }
        if($this->tradeTime !== null) {
            $time = 5 - (time() - $this->tradeTime);
            $this->menu->getInventory()->setItem(13, ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 0, $time)->setCustomName(TextFormat::RESET . TextFormat::GRAY . "Trade in $time seconds"));
            if($time <= 0) {
                if($this->sender->isOnline()) {
                    $this->sender->playDingSound();
                    $this->sender->removeCurrentWindow();
                }
                if($this->receiver->isOnline()) {
                    $this->receiver->playDingSound();
                    $this->receiver->removeCurrentWindow();
                }
                $manager->removeSession($key);
            }
        }
    }
}