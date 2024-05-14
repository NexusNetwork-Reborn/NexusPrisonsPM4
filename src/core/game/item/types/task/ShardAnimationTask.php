<?php

declare(strict_types = 1);

namespace core\game\item\types\task;

use core\game\item\types\Rarity;
use core\game\rewards\Reward;
use core\game\rewards\types\ShardRewards;
use core\player\NexusPlayer;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class ShardAnimationTask extends Task {

    /** @var int */
    private $ticks = 0;

    /** @var int */
    private $amount;

    /** @var InvMenu */
    private $inventory;

    /** @var InvMenuInventory */
    private $actualInventory;

    /** @var NexusPlayer */
    private $owner;

    /** @var string */
    private $rarity;

    /** @var ShardRewards */
    private $rewards;

    /** @var Reward[] */
    private $finalRewards = [];

    /**
     * ShardAnimationTask constructor.
     *
     * @param NexusPlayer $owner
     * @param string $rarity
     * @param ShardRewards $rewards
     * @param int $amount
     */
    public function __construct(NexusPlayer $owner, string $rarity, ShardRewards $rewards, int $amount) {
        $this->owner = $owner;
        $this->rarity = $rarity;
        $this->rewards = $rewards;
        $this->amount = $amount;
        $this->inventory = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $this->inventory->setListener(InvMenu::readonly());
        $this->inventory->setName(TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$rarity] . "$rarity Shard");
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 8, 1);
        $glass->setCustomName(TextFormat::RESET . TextFormat::GRAY . "Rolling...");
        $this->actualInventory = $this->inventory->getInventory();
        for($i = $amount - 1; $i < $this->actualInventory->getSize(); $i++) {
            $this->actualInventory->setItem($i, $glass);
        }
        $this->inventory->setInventoryCloseListener(function(NexusPlayer $player, InvMenuInventory $inventory): void {
            $rewards = $this->roll();
            foreach($rewards as $reward) {
                $reward->executeCallback($player);
            }
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
            $this->finalRewards = $this->roll();
            $this->inventory->setInventoryCloseListener(function(NexusPlayer $player, InvMenuInventory $inventory): void {
                foreach($this->finalRewards as $reward) {
                    $reward->executeCallback($player);
                }
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
        for($i = 0; $i < $this->amount; $i++) {
            $reward = $rewards[$i] = $this->rewards->getReward();
            $this->actualInventory->setItem($i, $reward->executeCallback());
        }
        $this->owner->playOrbSound();
        return $rewards;
    }
}