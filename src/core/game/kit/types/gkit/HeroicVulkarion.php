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

class HeroicVulkarion extends GodKit {

    /**
     * Hero constructor.
     */
    public function __construct() {
        parent::__construct("Heroic Vulkarion", TextFormat::RED);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::RED . "Heroic Vulkarion" . TextFormat::RESET;
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
        $tier = $player->getDataSession()->getGodKitTier($this);
        foreach($set as $item) {
            if($item instanceof Sword) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::DARK_RED . " " . $item->getName());
                $lore = "Forged by a powerful race in the heart of a dying star.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::SCORCH),
                    EnchantmentManager::getEnchantment(Enchantment::FROSTBLADE)
                ];
                if($tier >= 1) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::PUMMEL);
                }
                if($tier >= 2) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::LIGHTNING);
                }
                if($tier >= 3) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::FRENZY);
                }
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_HEAD) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::DARK_RED . " " . $item->getName());
                $lore = "Equipment so powerful that it can only be worn by those with cybernetic enhancements";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::MANEUVER),
                    EnchantmentManager::getEnchantment(Enchantment::EXTINGUISH)
                ];
                if($tier >= 2) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD);
                }
                if($tier >= 3) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::CHIVALRY);
                }
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_CHESTPLATE) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::DARK_RED . " " . $item->getName());
                $lore = "Equipment so powerful that it can only be worn by those with cybernetic enhancements";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::MANEUVER),
                    EnchantmentManager::getEnchantment(Enchantment::OVERLOAD)
                ];
                if($tier >= 2) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED);
                }
                if($tier >= 3) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::ESCAPIST);
                }
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_LEGGINGS) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::DARK_RED . " " . $item->getName());
                $lore = "Equipment so powerful that it can only be worn by those with cybernetic enhancements";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::MANEUVER),
                    EnchantmentManager::getEnchantment(Enchantment::LAST_STAND)
                ];
                if($tier >= 2) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::ELEMENTAL_MASTERY);
                }
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_BOOTS) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::DARK_RED . " " . $item->getName());
                $lore = "Equipment so powerful that it can only be worn by those with cybernetic enhancements";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::MANEUVER),
                    EnchantmentManager::getEnchantment(Enchantment::GEARS),
                    EnchantmentManager::getEnchantment(Enchantment::ADRENALINE),
                    EnchantmentManager::getEnchantment(Enchantment::DEFLECT)
                ];
                $item = $this->enchant($item, $player, $enchantments, EnchantmentManager::getEnchantment(Enchantment::SYSTEM_REBOOT));
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