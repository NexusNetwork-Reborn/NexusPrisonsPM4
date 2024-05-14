<?php

declare(strict_types = 1);

namespace core\game\kit\types\gkit;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\EnchantmentPage;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Sword;
use core\game\kit\GodKit;
use core\player\NexusPlayer;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\utils\TextFormat;

class Blacksmith extends GodKit {

    /**
     * Executioner constructor.
     */
    public function __construct() {
        parent::__construct("Blacksmith", TextFormat::DARK_GRAY);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::DARK_GRAY . "Blacksmith" . TextFormat::RESET;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return array
     */
    public function giveTo(NexusPlayer $player, bool $give = true): array {
        $items = [];
        $level = $player->getDataSession()->getTotalXPLevel();
        $set = $this->levelToArmorSet($level);
        $amount = mt_rand(6, 12);
        $rarities = [
            Enchantment::UNCOMMON,
            Enchantment::ELITE,
            Enchantment::ULTIMATE,
            Enchantment::LEGENDARY
        ];
        if($player->getDataSession()->getGodKitTier($this) > 1) {
            $rarities = [
                Enchantment::UNCOMMON,
                Enchantment::ELITE,
                Enchantment::ULTIMATE,
                Enchantment::LEGENDARY,
                Enchantment::GODLY
            ];
        }
        for($i = 0; $i < $amount; $i++) {
            $items[] = (new MysteryEnchantmentBook(Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$rarities[array_rand($rarities)]]))->toItem()->setCount(1);
        }
        $amount = mt_rand(5, 10);
        for($i = 0; $i < $amount; $i++) {
            $rand = mt_rand(1, 5);
            $items[] = (new EnchantmentPage($rarities[array_rand($rarities)], $rand, $rand))->toItem()->setCount(1);
        }
        $amount = mt_rand(1, 3);
        for($i = 0; $i < $amount; $i++) {
            if(mt_rand(1, 2) === 1) {
                $items[] = (new WhiteScroll())->toItem()->setCount(1);
            }
            else {
                $items[] = (new BlackScroll(mt_rand(80, 100)))->toItem()->setCount(1);
            }
        }
        $item = $set[array_rand($set)];
        for($i = 0; $i < 3; $i++) {
            if($item instanceof Armor) {
                $enchantment = EnchantmentManager::getRandomFightingEnchantment(Enchantment::LEGENDARY, Enchantment::SLOT_ARMOR, true);
                $item->addEnchantment(new EnchantmentInstance($enchantment, mt_rand(1, $enchantment->getMaxLevel())));
            }
            if($item instanceof Sword) {
                $enchantment = EnchantmentManager::getRandomFightingEnchantment(Enchantment::LEGENDARY, Enchantment::SLOT_SWORD, true);
                $item->addEnchantment(new EnchantmentInstance($enchantment, mt_rand(1, $enchantment->getMaxLevel())));
            }
        }
        $items[] = $item;
        $items[] = (new Energy(mt_rand(600000, 1200000)))->toItem()->setCount(1);
        if($give) {
            foreach($items as $item) {
                if($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                }
                else {
                    if($item->getCount() > 64) {
                        $item->setCount(64);
                    }
                    $player->getWorld()->dropItem($player->getPosition(), $item);
                }
            }
        }
        return $items;
    }
}