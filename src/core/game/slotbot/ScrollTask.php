<?php

namespace core\game\slotbot;

use core\game\item\types\CustomItem;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Fireworks;
use core\level\entity\types\FireworksRocket;
use core\Nexus;
use pocketmine\entity\Location;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\FireExtinguishSound;

class ScrollTask extends Task{

    /** @var SlotBotUI */
    private $ui;

    private $iteration;

    public const ROLLING_SLOTS = [
        1 => [2, 11, 20, 29, 38],
        2 => [3, 12, 21, 30, 39],
        3 => [4, 13, 22, 31, 40],
        4 => [5, 14, 23, 32, 41],
        5 => [6, 15, 24, 33, 42]
    ];

    //private const REWARD_SLOTS = self::ROLLING_SLOTS[3];

    public function __construct(SlotBotUI &$ui, int $iteration = 0)
    {
        $this->ui = &$ui;
        $this->iteration = $iteration;
    }

    public function onRun(): void
    {
        if(!$this->ui->isTerminated()) {
            $inv = $this->ui->getMenu()->getInventory();
            if ($this->iteration === 0) {
                foreach (self::ROLLING_SLOTS as $i => $slots) {
                    if ($i <= $this->ui->getRequestedRolls()) {
                        foreach ($slots as $slot) {
                            $inv->setItem($slot, $this->ui->getRewardSession()->getRandomReward());
                        }
                    }
                }
            } elseif($this->iteration < 30) {
                foreach (self::ROLLING_SLOTS as $i => $slots) {
                    if ($i <= $this->ui->getRequestedRolls()) {
                        $slots = array_reverse($slots);
                        foreach ($slots as $o => $slot) {
                            if (strlen((string)$slot) === 1) $inv->setItem($slot, $this->ui->getRewardSession()->getRandomReward());
                            else $inv->setItem($slot, $inv->getItem($slots[$o + 1]));
                        }
                    }
                }
            }

            $this->ui->getPlayer()->broadcastSound(new ClickSound());
            $this->continue();
        }
    }

    public function continue() : void{
        $scheduler = Nexus::getInstance()->getScheduler();
        if($this->iteration < 30 && !$this->ui->isTerminated()) {
            $this->setHandler(null);
            $this->iteration++;

            $scheduler->scheduleDelayedTask($this, 5);
        } elseif($this->iteration >= 30) {
            $this->iteration++;
            $inv = $this->ui->getMenu()->getInventory();
            foreach (self::ROLLING_SLOTS as $i => $slots) {
                if ($i <= $this->ui->getRequestedRolls()) {
                    foreach ($slots as $o => $slot) {
                        if ($o !== 2) $inv->setItem($slot, ItemFactory::air());
                    }
                }
            }
            $this->ui->incrementRoll();
            $this->ui->getPlayer()->broadcastSound(new FireExtinguishSound(), [$this->ui->getPlayer()]);
        }
    }

}