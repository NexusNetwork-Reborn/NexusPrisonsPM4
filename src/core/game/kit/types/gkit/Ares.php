<?php

declare(strict_types = 1);

namespace core\game\kit\types\gkit;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Sword;
use core\game\kit\GodKit;
use core\player\NexusPlayer;
use pocketmine\utils\TextFormat;

class Ares extends GodKit {

    /**
     * Ares constructor.
     */
    public function __construct() {
        parent::__construct("Ares", TextFormat::GOLD);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::GOLD . "Ares" . TextFormat::RESET;
    }

    /**
     * @param NexusPlayer $player
     * @param bool $give
     *
     * @return array
     */
    public function giveTo(NexusPlayer $player, bool $give = true): array {
        $items = [];
        $level = $player->getDataSession()->getTotalXPLevel();
        $set = $this->levelToArmorSet($level);
        foreach($set as $item) {
            if($item instanceof Sword) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::GOLD . " " . $item->getName());
                $lore = "Forged by the god of war himself in the ragin flames of the underworld.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::LUCKY),
                    EnchantmentManager::getEnchantment(Enchantment::LIGHTNING),
                    EnchantmentManager::getEnchantment(Enchantment::LIFESTEAL),
                    EnchantmentManager::getEnchantment(Enchantment::CANNIBALISM),
                    EnchantmentManager::getEnchantment(Enchantment::PUMMEL),
                    EnchantmentManager::getEnchantment(Enchantment::FAMINE)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_HEAD) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::GOLD . " " . $item->getName());
                $lore = "Forged by the god of war himself in the ragin flames of the underworld.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::AEGIS),
                    EnchantmentManager::getEnchantment(Enchantment::ELEMENTAL_MASTERY),
                    EnchantmentManager::getEnchantment(Enchantment::HARDEN),
                    EnchantmentManager::getEnchantment(Enchantment::EXTINGUISH),
                    EnchantmentManager::getEnchantment(Enchantment::CROUCH)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_CHESTPLATE) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::GOLD . " " . $item->getName());
                $lore = "Forged by the god of war himself in the ragin flames of the underworld.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::AEGIS),
                    EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD),
                    EnchantmentManager::getEnchantment(Enchantment::BLOOD_MAGIC),
                    EnchantmentManager::getEnchantment(Enchantment::OVERLOAD),
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_LEGGINGS) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::GOLD . " " . $item->getName());
                $lore = "Forged by the god of war himself in the ragin flames of the underworld.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::AEGIS),
                    EnchantmentManager::getEnchantment(Enchantment::ACID_BLOOD),
                    EnchantmentManager::getEnchantment(Enchantment::CURSE),
                    EnchantmentManager::getEnchantment(Enchantment::CROUCH),
                    EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_BOOTS) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::GOLD . " " . $item->getName());
                $lore = "Forged by the god of war himself in the ragin flames of the underworld.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::AEGIS),
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD),
                    EnchantmentManager::getEnchantment(Enchantment::ELEMENTAL_MASTERY),
                    EnchantmentManager::getEnchantment(Enchantment::GEARS)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            $items[] = $item;
        }
        $multiplier = $player->getDataSession()->getGodKitTier($this) + 1;
        $items[] = (new BlackScroll(mt_rand(15 * $multiplier, $multiplier * 25)))->toItem()->setCount(1);
        $items[] = (new BlackScroll(mt_rand(15 * $multiplier, $multiplier * 25)))->toItem()->setCount(1);
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