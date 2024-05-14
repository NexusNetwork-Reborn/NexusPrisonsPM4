<?php

namespace core\command\inventory;

use core\game\economy\EconomyCategory;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\Rarity;
use core\player\NexusPlayer;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class EnchanterInventory extends InvMenu {

    const RARITIES = [
        Rarity::SIMPLE,
        Rarity::UNCOMMON,
        Rarity::ELITE,
        Rarity::ULTIMATE,
        Rarity::LEGENDARY
    ];

    const RARITY_TO_ENERGY = [
        Rarity::SIMPLE => 25000,
        Rarity::UNCOMMON => 50000,
        Rarity::ELITE => 100000,
        Rarity::ULTIMATE => 250000,
        Rarity::LEGENDARY => 500000
    ];

    /** @var string[] */
    private $slots = [];

    /**
     * EnchanterInventory constructor.
     */
    public function __construct() {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_CHEST));
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::DARK_PURPLE . TextFormat::BOLD . "Enchanter");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if(($slot >= 11 and $slot <= 15) or ($slot >= 20 and $slot <= 24)) {
                $rarity = $this->slots[$slot];
                $price = self::RARITY_TO_ENERGY[$rarity];
                $count = 1;
                if($slot >= 20 and $slot <= 24) {
                    $price = self::RARITY_TO_ENERGY[$rarity] * 10;
                    $count = 10;
                }
                if(!$player->canPayEnergy($price)) {
                    $player->playErrorSound();
                    $player->sendTranslatedMessage("notEnoughEnergy");
                    $player->removeCurrentWindow();
                    return;
                }
                $item = (new MysteryEnchantmentBook($rarity))->toItem()->setCount($count);
                if(!$player->getInventory()->canAddItem($item)) {
                    $player->playErrorSound();
                    $player->sendTranslatedMessage("fullInventory");
                    $player->removeCurrentWindow();
                    return;
                }
                if($player->payEnergy($price)) {
                    $player->getInventory()->addItem($item);
                    $player->playOrbSound();
                }
            }
        }));
    }

    public function initItems(): void {
        $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::LIGHT_GRAY())->asItem();
        $glass->setCustomName(" ");
        $count = 0;
        for($i = 0; $i < 27; $i++) {
            if($i >= 11 and $i <= 15) {
                $rarity = self::RARITIES[$count % 5];
                $energy = self::RARITY_TO_ENERGY[$rarity];
                $count++;
                $this->slots[$i] = $rarity;
                $display = (new MysteryEnchantmentBook($rarity))->toItem()->setCount(1);
                $lore = $display->getLore();
                $lore = array_merge($lore, [
                    "",
                    TextFormat::RESET . TextFormat::GRAY . "Price: " . TextFormat::BOLD . TextFormat::WHITE . number_format($energy) . TextFormat::AQUA . " Energy",
                    "",
                    TextFormat::RESET . TextFormat::GRAY . "Click to purchase 1 book"
                ]);
                $display->setLore($lore);
                $this->getInventory()->setItem($i, $display);
            }
            elseif($i >= 20 and $i <= 24) {
                $rarity = self::RARITIES[$count % 5];
                $energy = self::RARITY_TO_ENERGY[$rarity] * 10;
                $count++;
                $this->slots[$i] = $rarity;
                $display = (new MysteryEnchantmentBook($rarity))->toItem()->setCount(10);
                $lore = $display->getLore();
                $lore = array_merge($lore, [
                    "",
                    TextFormat::RESET . TextFormat::GRAY . "Price: " . TextFormat::BOLD . TextFormat::WHITE . number_format($energy) . TextFormat::AQUA . " Energy",
                    "",
                    TextFormat::RESET . TextFormat::GRAY . "Click to purchase 10 books"
                ]);
                $display->setLore($lore);
                $this->getInventory()->setItem($i, $display);
            }
            else {
                $this->getInventory()->setItem($i, $glass);
            }
        }
    }
}