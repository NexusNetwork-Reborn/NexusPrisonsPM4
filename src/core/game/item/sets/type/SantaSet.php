<?php

namespace core\game\item\sets\type;

use core\game\item\sets\Set;
use core\game\item\sets\SetManager;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\color\Color;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class SantaSet extends Set implements Listener {

    public function getName(): string
    {
        return "Santa";
    }

    public function getColor(): string
    {
        return TextFormat::RED;
    }

    /**
     * @return Item
     */
    public function getHandItem(): Item
    {
        $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE);

        if($item instanceof Pickaxe) {
            $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Santa " . TextFormat::GRAY . " Pickaxe");
            $item->setOriginalLore([
                "",
                TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Set Bonus: " . TextFormat::RED . "Santa",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::RED. "+5% XP.",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::RED . "+2.5% Energy",
                "",
                TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Santa armor pieces)",
            ]);
            $item->getNamedTag()->setString(SetManager::SET, "santa");
            $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
        }

        return $item;
    }

    /**
     * @return Item[]
     */
    public function getArmor(): array
    {
        $head = ItemFactory::getInstance()->get(ItemIds::LEATHER_CAP);
        $chest = ItemFactory::getInstance()->get(ItemIds::LEATHER_TUNIC);
        $leggings = ItemFactory::getInstance()->get(ItemIds::LEATHER_PANTS);
        $boots = ItemFactory::getInstance()->get(ItemIds::LEATHER_BOOTS);
        $armorLore = [
            "",
            TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Set Bonus: " . TextFormat::RED . "Santa",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::RED . "+20% XP Gain",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::RED . "+10% Energy Gain",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::RED . "Passive Good Luck",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::RED . "Passive Ore Magnet VI",
            "",
            TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Santa armor pieces)",
            "",
            TextFormat::RESET . TextFormat::YELLOW . "This Armor is as strong as Diamond",
        ];

        if($head instanceof Armor && $chest instanceof Armor && $leggings instanceof Armor && $boots instanceof Armor) {
            $head->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Santa " . TextFormat::GRAY . "Helmet");
            $head->setOriginalLore($armorLore);
            $head->setCustomColor(new Color(255, 0, 0));
            $head->getNamedTag()->setString(SetManager::SET, "santa");
            $chest->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Santa " . TextFormat::GRAY . "Chestplate");
            $chest->setOriginalLore($armorLore);
            $chest->setCustomColor(new Color(255, 0, 0));
            $chest->getNamedTag()->setString(SetManager::SET, "santa");
            $leggings->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Santa " . TextFormat::GRAY . "Leggings");
            $leggings->setOriginalLore($armorLore);
            $leggings->setCustomColor(new Color(255, 0, 0));
            $leggings->getNamedTag()->setString(SetManager::SET, "santa");
            $boots->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Santa " . TextFormat::GRAY .  "Boots");
            $boots->setOriginalLore($armorLore);
            $boots->setCustomColor(new Color(255, 0, 0));
            $boots->getNamedTag()->setString(SetManager::SET, "santa");
        }

        return [$head, $chest, $leggings, $boots];
    }
}