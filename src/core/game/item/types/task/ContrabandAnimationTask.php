<?php

declare(strict_types = 1);

namespace core\game\item\types\task;

use core\game\item\types\Rarity;
use core\game\rewards\Reward;
use core\game\rewards\types\ContrabandRewards;
use core\player\NexusPlayer;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class ContrabandAnimationTask extends Task {

    /** @var int */
    private $ticks = 0;

    /** @var InvMenu */
    private $inventory;

    /** @var InvMenuInventory */
    private $actualInventory;

    /** @var NexusPlayer */
    private $owner;

    /** @var string */
    private $rarity;

    /** @var ContrabandRewards */
    private $rewards;

    /** @var Reward[] */
    private $finalRewards = [];

    /**
     * ContrabandAnimationTask constructor.
     *
     * @param NexusPlayer $owner
     * @param string $rarity
     * @param ContrabandRewards $rewards
     */
    public function __construct(NexusPlayer $owner, string $rarity, ContrabandRewards $rewards) {
        $this->owner = $owner;
        $this->rarity = $rarity;
        $this->rewards = $rewards;
        $this->inventory = InvMenu::create(InvMenu::TYPE_HOPPER);
        $this->inventory->setListener(InvMenu::readonly());
        $this->inventory->setName(TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "$rarity Contraband");
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 8, 10);
        $glass->setCustomName(TextFormat::RESET . TextFormat::GRAY . "Rolling...");
        $this->actualInventory = $this->inventory->getInventory();
        $this->actualInventory->setItem(2, $glass);
        $this->inventory->setInventoryCloseListener(function(NexusPlayer $player, InvMenuInventory $inventory): void {
            $this->finalRoll($player);
            $this->cancel();
        });
        $this->inventory->send($owner);
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        if($this->owner === null or $this->owner->isOnline() === false) {
            $this->cancel();
            return;
        }
        if($this->ticks % 16 === 0) {
            $item = $this->actualInventory->getItem(2);
            $this->actualInventory->setItem(2, $item->setCount(10 - ($this->ticks / 16)));
        }
        $this->ticks++;
        if($this->ticks < 20 and $this->ticks % 2 == 0) {
            $this->roll();
            return;
        }
        if($this->ticks < 40 and $this->ticks % 5 == 0) {
            $this->roll();
            return;
        }
        if($this->ticks < 120 and $this->ticks % 7 == 0) {
            $this->roll();
            return;
        }
        if($this->ticks < 160 and $this->ticks % 13 == 0) {
            $this->roll();
            return;
        }
        if($this->ticks === 160) {
            $this->finalRewards = $this->finalRoll($this->owner);
            $this->inventory->setInventoryCloseListener(function(NexusPlayer $player, InvMenuInventory $inventory): void {
                $this->cancel();
            });
            return;
        }
        if($this->ticks === 200) {
            $this->owner->playDingSound();
        }
        if($this->ticks === 300) {
            $this->owner->removeCurrentWindow();
        }
    }

    /**
     * @return array|Reward[]
     */
    public function roll(): array {
        /** @var Reward[] $rewards */
        $rewards = [];
        for($i = 0; $i <= 4; $i++) {
            if($i === 2) {
                continue;
            }
            $reward = $rewards[$i] = $this->rewards->getReward();
            $this->actualInventory->setItem($i, $reward->executeCallback());
        }
        $this->owner->playOrbSound();
        return $rewards;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return Item[]
     */
    public function finalRoll(NexusPlayer $player): array {
        /** @var Item[] $rewards */
        $rewards = [];
        for($i = 0; $i <= 4; $i++) {
            if($i === 2) {
                continue;
            }
            $reward = $rewards[$i] = $this->rewards->getReward()->executeCallback($player);
            $this->actualInventory->setItem($i, $reward);
        }
        $this->owner->playOrbSound();
        return $rewards;
    }
}