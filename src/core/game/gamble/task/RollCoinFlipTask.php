<?php

namespace core\game\gamble\task;

use core\game\gamble\CoinFlipEntry;
use core\game\gamble\event\CoinFlipLoseEvent;
use core\game\gamble\event\CoinFlipWinEvent;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ClickSound;

class RollCoinFlipTask extends Task {

    /** @var CoinFlipEntry */
    private $ownerEntry;

    /** @var CoinFlipEntry */
    private $targetEntry;

    /** @var bool */
    private $chosen = false;

    /** @var null|CoinFlipEntry */
    private $winner = null;

    /** @var null|CoinFlipEntry */
    private $loser;

    /** @var InvMenu */
    private $inventory;

    /** @var InvMenuInventory */
    private $actualInventory;

    /** @var int */
    private $ticks = 0;

    /** @var int */
    private $countDown = 100;

    /** @var int */
    private $delay = 5;

    /** @var int */
    private $rolls = 0;

    /**
     * RollCoinFlipTask constructor.
     *
     * @param CoinFlipEntry $ownerEntry
     * @param CoinFlipEntry $targetEntry
     */
    public function __construct(CoinFlipEntry $ownerEntry, CoinFlipEntry $targetEntry) {
        $this->ownerEntry = $ownerEntry;
        $this->targetEntry = $targetEntry;
        $this->inventory = InvMenu::create(InvMenu::TYPE_HOPPER);
        $this->inventory->setListener(InvMenu::readonly());
        $this->inventory->setName(TextFormat::BOLD . TextFormat::YELLOW . "Rolling...");
        $this->actualInventory = $this->inventory->getInventory();
        $ownerEntry->getOwner()->sendDelayedWindow($this->inventory);
        $targetEntry->getOwner()->sendDelayedWindow($this->inventory);
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        $owner = $this->ownerEntry->getOwner();
        $target = $this->targetEntry->getOwner();
        if($owner === null or $owner->isOnline() === false or $target === null or $target->isOnline() === false) {
            $this->cancel();
            return;
        }
        if($this->delay-- > 0) {
            return;
        }
        if($this->countDown > 0) {
            if($this->countDown % 20 == 0) {
                $count = floor($this->countDown / 20);
                $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 8, $count);
                $glass->setCustomName(TextFormat::RESET . TextFormat::GRAY . "Starting in $count" . "s");
                $this->actualInventory->setItem(2, $glass);
                $owner = $this->ownerEntry->getOwner();
                $owner->getWorld()->addSound($owner->getPosition(), new ClickSound(), [$owner]);
                $owner = $this->targetEntry->getOwner();
                $owner->getWorld()->addSound($owner->getPosition(), new ClickSound(), [$owner]);
            }
            $this->countDown--;
            return;
        }
        $this->ticks++;
        if(!$this->chosen) {
            if($this->ticks < 40 and $this->ticks % 2 == 0) {
                $this->roll();
                return;
            }
            if($this->ticks < 80 and $this->ticks % 5 == 0) {
                $this->roll();
                return;
            }
            if($this->ticks < 140 and $this->ticks % 7 == 0) {
                $this->roll();
                return;
            }
            if($this->ticks < 180 and $this->ticks % 13 == 0) {
                $this->roll();
                return;
            }
            if($this->ticks === 220) {
                if(!$this->chosen) {
                    $this->finalRoll();
                }
                return;
            }
        }
        if($this->ticks === 300) {
            $owner->removeCurrentWindow();
            $target->removeCurrentWindow();
        }
    }

    public function roll(): void {
        $this->rolls++;
        $entries = [
            $this->ownerEntry,
            $this->targetEntry
        ];
        /** @var CoinFlipEntry $entry */
        $entry = $entries[$this->rolls % 2];
        $color = $entry->getColor();
        $item = $this->colorToItem($color);
        $item->setCustomName(TextFormat::RESET . $color . TextFormat::BOLD . $entry->getOwner()->getName());
        $this->actualInventory->setItem(2, $item);
        $this->ownerEntry->getOwner()->playNoteSound(floor($this->rolls / 2));
        $this->targetEntry->getOwner()->playNoteSound(floor($this->rolls / 2));
        foreach($this->actualInventory->getViewers() as $viewer) {
            if($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                $viewer->getNetworkSession()->getInvManager()->syncContents($this->actualInventory);
            }
        }
    }

    public function finalRoll(): void {
        $this->chosen = true;
        Nexus::getInstance()->getGameManager()->getGambleManager()->removeActiveCoinFlip($this);
        if(50 >= mt_rand(1, 100)) {
            $this->winner = $this->ownerEntry;
            $this->loser = $this->targetEntry;
        }
        else {
            $this->winner = $this->targetEntry;
            $this->loser = $this->ownerEntry;
        }
        $color = $this->winner->getColor();
        $item = $this->colorToItem($color);
        $item->setCustomName(TextFormat::RESET . $color . TextFormat::BOLD . $this->winner->getOwner()->getName());
        $item->setLore([TextFormat::RESET . $color . TextFormat::BOLD . $this->winner->getOwner()->getName() . TextFormat::RESET . TextFormat::GRAY . " has won the Coin Flip!"]);
        $this->actualInventory->setItem(2, $item);
        foreach($this->actualInventory->getViewers() as $viewer) {
            if($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                $viewer->getNetworkSession()->getInvManager()->syncContents($this->actualInventory);
            }
        }
        $winner = $this->winner->getOwner();
        $loser = $this->loser->getOwner();
        $winner->playConsecutiveDingSound();
        $loser->playConsecutiveDingSound();
        $amount = $this->getOwnerEntry()->getAmount();
        $gambleManager = Nexus::getInstance()->getGameManager()->getGambleManager();
        $gambleManager->addWin($winner);
        $gambleManager->addLoss($loser);
        $gambleManager->getRecord($winner, $wins, $losses);
        $gambleManager->getRecord($loser, $wins2, $losses2);
        $ev = new CoinFlipWinEvent($this->winner->getOwner(), $amount);
        $ev->call();
        $ev = new CoinFlipLoseEvent($this->loser->getOwner(), $amount);
        $ev->call();
        $winTotal = $amount * 2;
        $loserColor = $this->loser->getColor();
        Server::getInstance()->broadcastMessage(TextFormat::GRAY . TextFormat::BOLD . "** " . TextFormat::RESET . $color . "■ " . TextFormat::GREEN . $winner->getName() . TextFormat::GRAY . " ($wins-$losses) has defeated " . $loserColor . "■ " . TextFormat::RED . $loser->getName() . TextFormat::GRAY . " ($wins2-$losses2) in a $" . number_format($winTotal) . " /coinflip");
        $deduct = $winner->getDataSession()->getRank()->getFeeDeduction();
        if($deduct > 0) {
            $percent = $deduct * 100;
            $deduct = floor($winTotal * $deduct);
            $winTotal -= $deduct;
            $winner->sendMessage(TextFormat::GRAY . "(-$percent% tax. Unlock Martian+ rank to have 0% tax)");
        }
        $winner->getDataSession()->addToBalance($winTotal);
    }

    /**
     * @param string $color
     *
     * @return Item
     */
    public function colorToItem(string $color): Item {
        switch($color) {
            case TextFormat::RED:
                return ItemFactory::getInstance()->get(ItemIds::WOOL, 14, 1);
                break;
            case TextFormat::GOLD:
                return ItemFactory::getInstance()->get(ItemIds::WOOL, 1, 1);
                break;
            case TextFormat::YELLOW:
                return ItemFactory::getInstance()->get(ItemIds::WOOL, 4, 1);
                break;
            case TextFormat::GREEN:
                return ItemFactory::getInstance()->get(ItemIds::WOOL, 5, 1);
                break;
            case TextFormat::AQUA:
                return ItemFactory::getInstance()->get(ItemIds::WOOL, 3, 1);
                break;
            case TextFormat::DARK_PURPLE:
                return ItemFactory::getInstance()->get(ItemIds::WOOL, 10, 1);
                break;
            case TextFormat::GRAY:
                return ItemFactory::getInstance()->get(ItemIds::WOOL, 8, 1);
                break;
            case TextFormat::BLACK:
                return ItemFactory::getInstance()->get(ItemIds::WOOL, 15, 1);
                break;
            default:
                return ItemFactory::getInstance()->get(ItemIds::WOOL, 0, 1);
                break;
        }
    }

    /**
     * @return CoinFlipEntry
     */
    public function getOwnerEntry(): CoinFlipEntry {
        return $this->ownerEntry;
    }

    /**
     * @return CoinFlipEntry
     */
    public function getTargetEntry(): CoinFlipEntry {
        return $this->targetEntry;
    }
}