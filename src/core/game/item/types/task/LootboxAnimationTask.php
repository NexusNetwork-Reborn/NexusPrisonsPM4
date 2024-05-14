<?php

declare(strict_types = 1);

namespace core\game\item\types\task;

use core\game\rewards\Reward;
use core\game\rewards\types\LootboxRewards;
use core\player\NexusPlayer;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use pocketmine\block\VanillaBlocks;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class LootboxAnimationTask extends Task {

    const REWARD_COUNT_TO_SLOTS_MAP = [
        1 => [13],
        2 => [12, 14],
        3 => [12, 13, 14],
        4 => [11, 12, 14, 15],
        5 => [11, 12, 13, 14, 15],
        6 => [10, 11, 12, 14, 15, 16],
        7 => [10, 11, 12, 13, 14, 15, 16],
        8 => [9, 10, 11, 12, 14, 15, 16, 17],
        9 => [9, 10, 11, 12, 13, 14, 15, 16, 17],
    ];

    /** @var int */
    private $ticks = 0;

    /** @var InvMenu */
    private $inventory;

    /** @var InvMenuInventory */
    private $actualInventory;

    /** @var NexusPlayer */
    private $owner;

    /** @var LootboxRewards */
    private $rewards;

    /** @var Reward[] */
    private $finalRewards = [];

    /**
     * LootboxAnimationTask constructor.
     *
     * @param NexusPlayer $owner
     * @param LootboxRewards $rewards
     */
    public function __construct(NexusPlayer $owner, LootboxRewards $rewards) {
        $this->owner = $owner;
        $this->rewards = $rewards;
        $this->inventory = InvMenu::create(InvMenu::TYPE_CHEST);
        $this->inventory->setListener(InvMenu::readonly());
        $this->inventory->setName(TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . "Lootbox: " . $rewards->getColoredName());
        $glass = VanillaBlocks::ELEMENT_ZERO()->asItem();
        $glass->setCustomName(TextFormat::RESET . TextFormat::GRAY . "Rolling...");
        $this->actualInventory = $this->inventory->getInventory();
        $glass->setCount(10);
        $this->actualInventory->setItem(4, $glass);
        $this->actualInventory->setItem(22, $glass);
        $this->inventory->setInventoryCloseListener(function(NexusPlayer $player, InvMenuInventory $inventory): void {
            $rewards = $this->roll();
            Server::getInstance()->broadcastMessage(" ");
            Server::getInstance()->broadcastMessage(TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GOLD . $player->getName() . " opened a " . TextFormat::BOLD . TextFormat::WHITE . "Lootbox: " . $this->rewards->getColoredName() . TextFormat::RESET . TextFormat::GOLD . " and received:");
            foreach($rewards as $reward) {
                $reward = $reward->executeCallback($player);
                Server::getInstance()->broadcastMessage(TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . $reward->getCount() . "x " . $reward->getCustomName());
            }
            Server::getInstance()->broadcastMessage(" ");
            $player->playOrbSound();
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
            $item = $this->actualInventory->getItem(4);
            $this->actualInventory->setItem(4, $item->setCount(max(1, 10 - ($this->ticks / 16))));
            $item = $this->actualInventory->getItem(22);
            $this->actualInventory->setItem(22, $item->setCount(max(1, 10 - ($this->ticks / 16))));
        }
        $this->ticks++;
        if($this->ticks < 20 and $this->ticks % 1 == 0) {
            $this->roll();
            return;
        }
        if($this->ticks < 40 and $this->ticks % 3 == 0) {
            $this->roll();
            return;
        }
        if($this->ticks < 120 and $this->ticks % 5 == 0) {
            $this->roll();
            return;
        }
        if($this->ticks < 160 and $this->ticks % 7 == 0) {
            $this->roll();
            return;
        }
        if($this->ticks === 160) {
            $this->finalRewards = $this->roll();
            $this->inventory->setInventoryCloseListener(function(NexusPlayer $player, InvMenuInventory $inventory): void {
                Server::getInstance()->broadcastMessage(" ");
                Server::getInstance()->broadcastMessage(TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GOLD . $player->getName() . " opened a " . TextFormat::BOLD . TextFormat::WHITE . "Lootbox: " . $this->rewards->getColoredName() . TextFormat::RESET . TextFormat::GOLD . " and received:");
                foreach($this->finalRewards as $reward) {
                    $reward = $reward->executeCallback($player);
                    Server::getInstance()->broadcastMessage(TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . $reward->getCount() . "x " . $reward->getCustomName());
                }
                Server::getInstance()->broadcastMessage(" ");
                $this->cancel();
            });
            return;
        }
        if($this->ticks === 200) {
            $this->owner->playOrbSound();
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
        $rewards = array_merge($this->rewards->getRoll([]), $this->rewards->getBonus());
        $finalRollRewards = $rewards;
        $count = $this->rewards->getRewardCount() + count($this->rewards->getBonus());
        $slots = self::REWARD_COUNT_TO_SLOTS_MAP[$count] ?? self::REWARD_COUNT_TO_SLOTS_MAP[9];
        foreach($slots as $slot) {
            $this->actualInventory->setItem($slot, array_shift($rewards)->executeCallback());
        }
        $this->owner->playDingSound();
        return $finalRollRewards;
    }
}