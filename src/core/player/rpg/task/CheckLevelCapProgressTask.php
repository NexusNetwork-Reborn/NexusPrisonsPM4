<?php

namespace core\player\rpg\task;

use core\game\auction\inventory\AuctionConfirmationInventory;
use core\game\item\enchantment\EnchantmentManager;
use core\Nexus;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use libs\utils\Task;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CheckLevelCapProgressTask extends Task {

    /** @var RPGManager */
    private $manager;

    /** @var int */
    private $day;

    /**
     * CheckLevelCapProgressTask constructor.
     *
     * @param RPGManager $manager
     */
    public function __construct(RPGManager $manager) {
        $this->manager = $manager;
        $this->day = RPGManager::getCurrentDay();
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        $day = RPGManager::getCurrentDay();
        if($day > $this->day) {
            $this->day = $day;
            $level = RPGManager::getLevelCap($day);
            if($level > 100) {
                $prestige = $level - 100;
                $level = 100;
                $level = TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "<" . TextFormat::AQUA . EnchantmentManager::getRomanNumber($prestige) . TextFormat::LIGHT_PURPLE . "> " . TextFormat::RESET . TextFormat::WHITE . $level . TextFormat::GRAY;
            }
            else {
                $level = TextFormat::WHITE . $level . TextFormat::GRAY;
            }
            Nexus::getInstance()->getServer()->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . "(!) Day " . TextFormat::WHITE . $day . TextFormat::GOLD . " /levelcap UNLOCKED!");
            Nexus::getInstance()->getServer()->broadcastMessage(TextFormat::GRAY . "You can now be up to level $level Mining!");
        }
    }
}