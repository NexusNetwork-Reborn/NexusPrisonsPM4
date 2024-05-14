<?php

namespace core\player\rpg;

use core\display\animation\AnimationException;
use core\display\animation\task\AnimationHeartbeatTask;
use core\display\animation\type\LevelUpAnimation;
use core\game\item\ItemManager;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Shard;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rpg\event\LevelUpEvent;
use core\player\rpg\task\CheckLevelCapProgressTask;
use core\translation\TranslationException;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class RPGManager {

    const LEVEL_CAPS = [
        1 => 45,
        2 => 60,
        3 => 75,
        4 => 80,
        5 => 85,
        7 => 90,
        9 => 95,
        10 => 100,
        14 => 101,
        19 => 102,
        24 => 103,
        30 => 104,
        36 => 105
    ];

    const MAX_PRESTIGE = 5;

    const MAX_LEVEL = 100;

    const MODIFIER = 0.00572;

    const ENERGY_MODIFIER = 0.01;

    const SATCHEL_MODIFIER = 0.02;

    /** @var Nexus */
    private $core;

    /**
     * RPGManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
    }

    public function init(): void {
        $this->core->getScheduler()->scheduleRepeatingTask(new CheckLevelCapProgressTask($this), 1);
    }

    /**
     * @return int
     */
    public static function getCurrentDay(): int {
        $day = (int)floor(((time() - Nexus::START) / 86400)) > 0 ? (int)floor(((time() - Nexus::START) / 86400)) : 0;
        return $day + 1;
    }

    /**
     * @param int|null $days
     *
     * @return int
     */
    public static function getLevelCap(?int $days = null): int {
        if($days === null) {
            $days = self::getCurrentDay();
        }
        if(isset(self::LEVEL_CAPS[$days])) {
            return self::LEVEL_CAPS[$days];
        }
        if($days >= 36) {
            return self::LEVEL_CAPS[36];
        }
        for($i = $days; $i >= 1; $i--) {
            if(isset(self::LEVEL_CAPS[$i])) {
                return self::LEVEL_CAPS[$i];
            }
        }
        return self::LEVEL_CAPS[1];
    }

    /**
     * @param int|null $days
     *
     * @return int
     */
    public static function getLevelCapXP(?int $days = null): int {
        $cap = self::getLevelCap($days);
        if($cap > 100) {
            $prestige = $cap - 100;
            return (XPUtils::levelToXP(100) + RPGManager::getPrestigeXP($prestige - 1));
        }
        return XPUtils::levelToXP(self::getLevelCap($days)) + 1;
    }

    /**
     * @param int $oldPrestige
     *
     * @return int|null
     */
    public static function getPrestigeXP(int $oldPrestige): ?int {
        switch($oldPrestige) {
            case 0:
                return 425000000;
            case 1:
                return 2400000000;
            case 2:
                return 12500000000;
            case 3:
                return 24500000000;
            case 4:
                return 72850000000;
        }
        return 1;
    }

    /**
     * @param NexusPlayer $player
     * @param int $oldLevel
     * @param int $newLevel
     *
     * @throws TranslationException
     * @throws AnimationException
     */
    public function handleLevelUp(NexusPlayer $player, int $oldLevel, int $newLevel): void {
        $ev = new LevelUpEvent($player, $oldLevel, $newLevel);
        $ev->call();
        $oldLevel = $player->getDataSession()->getMaxLevelObtained();
        if($newLevel > $player->getDataSession()->getMaxLevelObtained()){
            $player->getDataSession()->setMaxLevelObtained($newLevel);
        }
        $message = TextFormat::BOLD . TextFormat::GOLD . "\nLEVEL $newLevel";
        if($newLevel > $oldLevel) {
            $items = [];
            $session = $player->getDataSession();
            $bb = $session->getBankBlock();
            for($i = $oldLevel + 1; $i <= $newLevel; $i++) {
                $bb += ($bb * 0.0325);
                $rarity = ItemManager::getRarityByLevel($i);
                $count = (int)round(3 + ($i * 0.75));
                if($i % 5 == 0) {
                    $rarity .= "Contraband";
                    $count = 1;
                }
                $this->broadcastMessage($player, $i);
                if(!isset($items[$rarity])) {
                    $items[$rarity] = $this->getAppropriateReward($player, $i);
                    continue;
                }
                $items[$rarity]->setCount($items[$rarity]->getCount() + $count);
            }
            $session->setBankBlock((int)$bb);
            $inventory = $player->getInventory();
            foreach($items as $item) {
                $player->addItem($item);
                $message .= TextFormat::BOLD . TextFormat::WHITE . TextFormat::GRAY . "\n * " . TextFormat::RESET . $item->getCustomName() . TextFormat::RESET . TextFormat::BOLD  .  TextFormat::GRAY . " * " . TextFormat::RESET . TextFormat::WHITE . $item->getCount();
            }
        }
        $message .= "\n\n";
        $player->sendMessage($message);
        $player->playDingSound();
        Nexus::getInstance()->getDisplayManager()->getAnimationManager()->addAnimation(new LevelUpAnimation($player));
    }

    /**
     * @param NexusPlayer $player
     * @param int $level
     *
     * @return Item
     */
    public function getAppropriateReward(NexusPlayer $player, int $level): Item {
        $rarity = ItemManager::getRarityByLevel($level);
        if($level % 5 === 0) {
            return (new Contraband($rarity))->toItem();
        }
        return (new Shard($rarity))->toItem()->setCount((int)round(3 + ($level * 0.75)));
    }

    /**
     * @param NexusPlayer $player
     * @param int $level
     */
    public function broadcastMessage(NexusPlayer $player, int $level): void {
        if($level % 5 === 0) {
            if($level !== 100) {
                $player->getServer()->broadcastMessage(TextFormat::BOLD . TextFormat::GRAY . " * " . TextFormat::WHITE . $player->getName() . TextFormat::GRAY . " LEVELED UP - " . $player->getDataSession()->getLevelTag($level) . TextFormat::BOLD . TextFormat::GRAY . " * ");
            }
            else {
                $player->getServer()->broadcastMessage(TextFormat::BOLD . TextFormat::AQUA . " * " . TextFormat::WHITE . $player->getName() . TextFormat::GOLD . " ATTAINED MAX UNPRESTIGED LEVEL" . TextFormat::GRAY . " - " . $player->getDataSession()->getLevelTag() . TextFormat::BOLD . TextFormat::AQUA . " * ");
            }
        }
    }
}