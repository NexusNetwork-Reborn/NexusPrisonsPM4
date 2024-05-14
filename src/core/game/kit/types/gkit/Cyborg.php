<?php

declare(strict_types = 1);

namespace core\game\kit\types\gkit;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Sword;
use core\game\kit\GodKit;
use core\player\NexusPlayer;
use pocketmine\utils\TextFormat;

class Cyborg extends GodKit {

    /**
     * Hero constructor.
     */
    public function __construct() {
        parent::__construct("Cyborg", TextFormat::DARK_AQUA);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::DARK_AQUA . "Cyborg" . TextFormat::RESET;
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
        foreach($set as $item) {
            if($item instanceof Sword) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Adamantium Blade");
                $lore = "Equipment so powerful that it can only be worn by those with cybernetic enhancements.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::FRENZY),
                    EnchantmentManager::getEnchantment(Enchantment::LIGHTNING),
                    EnchantmentManager::getEnchantment(Enchantment::LIFESTEAL),
                    EnchantmentManager::getEnchantment(Enchantment::LUCKY),
                    EnchantmentManager::getEnchantment(Enchantment::ANTI_GANK),
                    EnchantmentManager::getEnchantment(Enchantment::ENRAGE),
                    EnchantmentManager::getEnchantment(Enchantment::EXECUTE)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_HEAD) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Cybernetic Visor");
                $lore = "Equipment so powerful that it can only be worn by those with cybernetic enhancements";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD),
                    EnchantmentManager::getEnchantment(Enchantment::HARDEN),
                    EnchantmentManager::getEnchantment(Enchantment::CHIVALRY),
                    EnchantmentManager::getEnchantment(Enchantment::EXTINGUISH),
                    EnchantmentManager::getEnchantment(Enchantment::MANEUVER)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_CHESTPLATE) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Cybernetic Chestplate");
                $lore = "Equipment so powerful that it can only be worn by those with cybernetic enhancements";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::BLOOD_MAGIC),
                    EnchantmentManager::getEnchantment(Enchantment::CHIVALRY),
                    EnchantmentManager::getEnchantment(Enchantment::HARDEN),
                    EnchantmentManager::getEnchantment(Enchantment::TOXIC_MIST),
                    EnchantmentManager::getEnchantment(Enchantment::EXTINGUISH),
                    EnchantmentManager::getEnchantment(Enchantment::MANEUVER)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_LEGGINGS) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Biomechatronic Legs");
                $lore = "Equipment so powerful that it can only be worn by those with cybernetic enhancements";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::LUCKY),
                    EnchantmentManager::getEnchantment(Enchantment::ANTI_GANK),
                    EnchantmentManager::getEnchantment(Enchantment::LAST_STAND),
                    EnchantmentManager::getEnchantment(Enchantment::CACTUS),
                    EnchantmentManager::getEnchantment(Enchantment::INFERNO),
                    EnchantmentManager::getEnchantment(Enchantment::DEFLECT)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_BOOTS) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Cybernetic Boots");
                $lore = "Equipment so powerful that it can only be worn by those with cybernetic enhancements";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::GEARS),
                    EnchantmentManager::getEnchantment(Enchantment::DAMAGE_LIMITER),
                    EnchantmentManager::getEnchantment(Enchantment::ELEMENTAL_MASTERY),
                    EnchantmentManager::getEnchantment(Enchantment::ADRENALINE),
                    EnchantmentManager::getEnchantment(Enchantment::MANEUVER),
                    EnchantmentManager::getEnchantment(Enchantment::DEFLECT),
                ];
                $item = $this->enchant($item, $player, $enchantments, EnchantmentManager::getEnchantment(Enchantment::SYSTEM_REBOOT), 2);
            }
            $items[] = $item;
        }
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