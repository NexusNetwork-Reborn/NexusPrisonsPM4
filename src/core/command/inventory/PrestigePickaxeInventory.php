<?php

namespace core\command\inventory;

use core\game\item\ItemManager;
use core\game\item\types\vanilla\Pickaxe;
use core\game\kit\Kit;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class PrestigePickaxeInventory extends InvMenu {

    /** @var Pickaxe */
    private $pickaxe;

    /**
     * PrestigePickaxeInventory constructor.
     *
     * @param NexusPlayer $player
     * @param Pickaxe $pickaxe
     */
    public function __construct(NexusPlayer $player, Pickaxe $pickaxe) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->initItems($player, $pickaxe);
        $this->pickaxe = $pickaxe;
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Pickaxe Prestige");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $inventory = $player->getInventory();
            if(!$inventory->contains($this->pickaxe)) {
                $player->playErrorSound();
                $player->removeCurrentWindow();
                $player->sendTranslatedMessage("itemNotFound");
                return;
            }
            if($slot === 31) {
                if(ItemManager::canPrestige($this->pickaxe)) {
                    $player->removeCurrentWindow();
                    $player->sendDelayedWindow(new SelectPrestigeInventory($player, $this->pickaxe));
                }
                else {
                    $player->removeCurrentWindow();
                    $player->playErrorSound();
                }
            }
        }));
    }

    /**
     * @param NexusPlayer $player
     * @param Pickaxe $pickaxe
     */
    public function initItems(NexusPlayer $player, Pickaxe $pickaxe): void {
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 15, 1);
        $glass->setCustomName(" ");
        for($i = 0; $i < 54; $i++) {
            if($i === 4) {
                $item = VanillaItems::BOOK()->setCount(1);
                $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Pickaxe Prestige");
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Prestiging your pickaxe gives you";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "a permanent buff to keep your";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "pickaxe forever!";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "To prestige a pickaxe you must first meet";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "the requirements needed of that pickaxe!";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Below is a list of each Prestige Perk!";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::AQUA . "Energy Mastery" . TextFormat::GRAY . ": +6 Charge Orb slots";
                $lore[] = TextFormat::RESET . TextFormat::AQUA . "XP Mastery" . TextFormat::GRAY . ": +20% XP";
                $lore[] = TextFormat::RESET . TextFormat::AQUA . "Hoarder" . TextFormat::GRAY . ": +50% Ores";
                $lore[] = TextFormat::RESET . TextFormat::AQUA . "Grinder" . TextFormat::GRAY . ": +30% Mining speed";
                $lore[] = TextFormat::RESET . TextFormat::AQUA . "Shard Mastery" . TextFormat::GRAY . ": 2.5x Shard chance";
                $lore[] = TextFormat::RESET . TextFormat::AQUA . "Meteorite Mastery" . TextFormat::GRAY . ": +20% ores from Meteorites";
                $lore[] = TextFormat::RESET . TextFormat::AQUA . "Clue Scroll Mastery" . TextFormat::GRAY . ": +10% chance at finding Clue Scrolls";
                $lore[] = TextFormat::RESET . TextFormat::AQUA . "Inquisitive" . TextFormat::GRAY . ": 1.0% chance to get an extra 10% XP in a bottle";
                $lore[] = TextFormat::RESET . TextFormat::AQUA . "Ore Extractor" . TextFormat::GRAY . ": 0.010% chance to find a generator";
                $lore[] = TextFormat::RESET . TextFormat::AQUA . "Forge Master" . TextFormat::GRAY . ": +30% chance to find Forge Fuel (Requires Player Prestige 1+)";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::DARK_RED . TextFormat::BOLD . "Warning: " . TextFormat::RESET . TextFormat::GRAY . "Prestiging your pickaxe will";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "cause it to lose all enchants!";
                $item->setLore($lore);
                $this->getInventory()->setItem($i, $item);
                continue;
            }
            if($i === 22) {
                $this->getInventory()->setItem($i, $pickaxe);
                continue;
            }
            if($i === 31) {
                $lore = [];
                if($pickaxe->getPrestige() >= 10) {
                    $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem();
                    $item->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "You have reached the max prestige!");
                }
                else {
                    ItemManager::getPrestigeRequirements($pickaxe->getPrestige(), $pickaxe->getBlockToolHarvestLevel(), $blocks, $levels);
                    if(ItemManager::canPrestige($pickaxe)) {
                        $color = TextFormat::GREEN;
                        $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GREEN())->asItem();
                        $item->setCustomName(TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Eligible for Prestige:");
                    }
                    else {
                        $color = TextFormat::RED;
                        $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem();
                        $item->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Ineligible for Prestige:");
                    }
                    $level = XPUtils::xpToLevel($pickaxe->getEnergy(), RPGManager::ENERGY_MODIFIER);
                    $lore[] = "";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . $color . TextFormat::BOLD . " * " . TextFormat::RESET . $color . "Item Level" . TextFormat::GRAY . " - " . TextFormat::WHITE . number_format($level) . TextFormat::GRAY . " / " . TextFormat::WHITE . number_format($levels);
                    $lore[] = TextFormat::RESET . $color . TextFormat::BOLD . " * " . TextFormat::RESET . $color . "Blocks Mined" . TextFormat::GRAY . " - " . TextFormat::WHITE . number_format($pickaxe->getBlocks()) . TextFormat::GRAY . " / " . TextFormat::WHITE . number_format($blocks);
                    if(ItemManager::canPrestige($pickaxe)) {
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . $color . TextFormat::BOLD . " >> Click to Prestige <<";
                    }
                }
                $item->setLore($lore);
                $this->getInventory()->setItem($i, $item);
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
    }
}