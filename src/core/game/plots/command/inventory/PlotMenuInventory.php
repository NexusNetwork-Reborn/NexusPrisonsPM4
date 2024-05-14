<?php

namespace core\game\plots\command\inventory;

use core\game\plots\command\forms\PlotManageForm;
use core\Nexus;
use core\player\NexusPlayer;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class PlotMenuInventory extends InvMenu {

    /**
     * PlotMenuInventory constructor.
     */
    public function __construct() {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_CHEST));
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Plots");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($slot === 4) {
                $player->removeCurrentWindow();
                $player->sendDelayedForm(new PlotManageForm($player));
            }
            if($slot === 12) {
                $world = Nexus::getInstance()->getServer()->getWorldManager()->getWorldByName("citizen");
            }
            if($slot === 13) {
                $world = Nexus::getInstance()->getServer()->getWorldManager()->getWorldByName("merchant");
            }
            if($slot === 14) {
                $world = Nexus::getInstance()->getServer()->getWorldManager()->getWorldByName("king");
            }
            if(isset($world)) {
                $player->removeCurrentWindow();
                $player->sendDelayedWindow(new PlotListSectionInventory($world));
            }
            return;
        }));
    }

    public function initItems(): void {
        for($i = 0; $i < 27; $i++) {
            if($i === 4) {
                $offers = VanillaItems::PAPER();
                $offers->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Plots");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Use this menu to manage";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "and teleport to plots";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Shows the plots that you own";
                $offers->setLore($lore);
                $this->getInventory()->setItem($i, $offers);
                continue;
            }
            if($i === 12) {
                $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::WHITE())->asItem()->setCount(1);
                $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . "Citizen");
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::RED . "Required Mining Level " . TextFormat::WHITE . TextFormat::BOLD . "45";
                $lore[] = TextFormat::RESET . TextFormat::RED . "OR Prestige" . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . " <" . TextFormat::AQUA . "I" . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . TextFormat::RED . " or higher";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::WHITE . "Price: " . TextFormat::GREEN . "$2,500,000";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Modifiers";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Raiding:" . TextFormat::GREEN . " Disabled";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Ore Magnet:" . TextFormat::RED . " -25%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Shard Discoverer:" . TextFormat::RED . " -25%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Momentum:" . TextFormat::RED . " -25%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Pet Cooldown:" . TextFormat::RED . " 2x";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Generator Limit:" . TextFormat::RED . " 12";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Generator Placement Limit:" . TextFormat::RED . " 4";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Energy Forges:" . TextFormat::RED . " Disabled";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Outpost XP Buff:" . TextFormat::RED . " -50%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Outpost Energy Buff:" . TextFormat::RED . " -50%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Plot per User / IP:" . TextFormat::RED . " 1";
                $item->setLore($lore);
                $this->getInventory()->setItem($i, $item);
                continue;
            }
            if($i === 13) {
                $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem()->setCount(1);
                $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Merchant");
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::RED . "Required Mining Level " . TextFormat::WHITE . TextFormat::BOLD . "100";
                $lore[] = TextFormat::RESET . TextFormat::RED . "OR Prestige" . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . " <" . TextFormat::AQUA . "I" . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . TextFormat::RED . " or higher";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::WHITE . "Price: " . TextFormat::GREEN . "$100,000,000";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Modifiers";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Raiding:" . TextFormat::GREEN . " Disabled";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Ore Magnet:" . TextFormat::RED . " -25%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Shard Discoverer:" . TextFormat::RED . " -25%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Momentum:" . TextFormat::RED . " -25%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Pet Cooldown:" . TextFormat::RED . " 2x";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Generator Limit:" . TextFormat::RED . " 16";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Generator Placement Limit:" . TextFormat::RED . " 6";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Energy Forges:" . TextFormat::RED . " Disabled";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Outpost XP Buff:" . TextFormat::RED . " -50%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Outpost Energy Buff:" . TextFormat::RED . " -50%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Plot per User / IP:" . TextFormat::RED . " 1";
                $item->setLore($lore);
                $this->getInventory()->setItem($i, $item);
                continue;
            }
            if($i === 14) {
                $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::LIGHT_BLUE())->asItem()->setCount(1);
                $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "King");
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::RED . "Required Prestige" . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . " <" . TextFormat::AQUA . "III" . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . TextFormat::RED . " or higher";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::WHITE . "Price: " . TextFormat::GREEN . "$1,000,000,000";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Modifiers";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Raiding:" . TextFormat::GREEN . " Disabled";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Ore Magnet:" . TextFormat::RED . " -25%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Shard Discoverer:" . TextFormat::RED . " -25%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Momentum:" . TextFormat::RED . " -25%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Pet Cooldown:" . TextFormat::RED . " 2x";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Generator Limit:" . TextFormat::RED . " 20";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Generator Placement Limit:" . TextFormat::RED . " 8";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Energy Forges:" . TextFormat::RED . " Disabled";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Outpost XP Buff:" . TextFormat::RED . " -50%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Outpost Energy Buff:" . TextFormat::RED . " -50%";
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . "Plot per User / IP:" . TextFormat::RED . " 1";
                $item->setLore($lore);
                $this->getInventory()->setItem($i, $item);
                continue;
            }
        }
    }
}