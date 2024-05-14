<?php

namespace core\command\inventory;

use core\command\task\TickLevelCapInventory;
use core\game\item\enchantment\EnchantmentManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use libs\utils\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\utils\TextFormat;

class LevelCapInventory extends InvMenu {

    /**
     * LevelCapInventory constructor.
     */
    public function __construct() {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "Level Cap");
        $this->setListener(self::readonly());
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TickLevelCapInventory($this), 20);
    }

    public function initItems(): void {
        $current = RPGManager::getCurrentDay();
        if($current >= 36) {
            $current = 36;
        }
        for($i = 0; $i < 36; $i++) {
            $day = $i + 1;
            $xp = RPGManager::getLevelCapXP($day);
            $level = RPGManager::getLevelCap($day);
            $prestige = 0;
            if($day <= $current) {
                $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GREEN())->asItem();
                if((!isset(RPGManager::LEVEL_CAPS[$day])) and $day !== $current) {
                    $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::WHITE())->asItem();
                }
                $status = "UNLOCKED";
                $color = TextFormat::GREEN;
            }
            else {
                $status = "LOCKED";
                $color = TextFormat::RED;
                $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem();
            }
            $item->setCount($day);
            $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . $color . "Day " . TextFormat::WHITE . $day);
            if($day === $current) {
                $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . $color . "Day $day" . TextFormat::RESET . TextFormat::GRAY . " (CURRENT)");
                $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
            }
            if($level > 100) {
                $prestige = $level - 100;
                $level = 100;
            }
            $levelTag = TextFormat::WHITE . $level;
            if($prestige > 0) {
                $levelTag .= TextFormat::BOLD . TextFormat::LIGHT_PURPLE . " <" . TextFormat::AQUA . EnchantmentManager::getRomanNumber($prestige) . TextFormat::LIGHT_PURPLE . "> ";
            }
            $lore = [];
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . $status;
            if($status === "LOCKED") {
                $lore[] = TextFormat::RESET . TextFormat::GRAY . Utils::secondsToTime(((($day - 1) * 86400) + Nexus::START) - time());
            }
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::RED . "Max Level: $levelTag";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "(" . TextFormat::WHITE . number_format($xp) . TextFormat::GRAY . " XP)";
            $item->setLore($lore);
            $this->getInventory()->setItem($i, $item);
        }
    }

    public function tick(): bool {
        $this->initItems();
        foreach($this->getInventory()->getViewers() as $viewer) {
            if($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                $viewer->getNetworkSession()->getInvManager()->syncContents($this->getInventory());
            }
        }
        return true;
    }
}