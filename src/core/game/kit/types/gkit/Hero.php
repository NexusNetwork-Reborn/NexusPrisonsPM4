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

class Hero extends GodKit {

    /**
     * Hero constructor.
     */
    public function __construct() {
        parent::__construct("Hero", TextFormat::WHITE);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::WHITE . "Hero" . TextFormat::RESET;
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
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Monster Slayer");
                $lore = "A set of gear that can only be wielded by the strongest heroes of this galaxy.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::FEARLESS),
                    EnchantmentManager::getEnchantment(Enchantment::FRENZY),
                    EnchantmentManager::getEnchantment(Enchantment::LUCKY),
                    EnchantmentManager::getEnchantment(Enchantment::PUMMEL),
                    EnchantmentManager::getEnchantment(Enchantment::LIGHTNING),
                    EnchantmentManager::getEnchantment(Enchantment::LIFESTEAL),
                    EnchantmentManager::getEnchantment(Enchantment::TRAP),
                    EnchantmentManager::getEnchantment(Enchantment::FLING),
                    EnchantmentManager::getEnchantment(Enchantment::SCORCH)
                ];
                $item = $this->enchant($item, $player, $enchantments, EnchantmentManager::getEnchantment(Enchantment::SILENCE));
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_HEAD) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Battle Helmet of the Hero");
                $lore = "A set of gear that can only be wielded by the strongest heroes of this galaxy.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::DEFLECT),
                    EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD),
                    EnchantmentManager::getEnchantment(Enchantment::HARDEN),
                    EnchantmentManager::getEnchantment(Enchantment::CHIVALRY),
                    EnchantmentManager::getEnchantment(Enchantment::EXTINGUISH),
                    EnchantmentManager::getEnchantment(Enchantment::MANEUVER)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_CHESTPLATE) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Hero's Platebody");
                $lore = "A set of gear that can only be wielded by the strongest heroes of this galaxy.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::OVERLOAD),
                    EnchantmentManager::getEnchantment(Enchantment::AEGIS),
                    EnchantmentManager::getEnchantment(Enchantment::DAMAGE_LIMITER),
                    EnchantmentManager::getEnchantment(Enchantment::FATTY),
                    EnchantmentManager::getEnchantment(Enchantment::ESCAPIST)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_LEGGINGS) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Hero's Plateleggings");
                $lore = "A set of gear that can only be wielded by the strongest heroes of this galaxy.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::LUCKY),
                    EnchantmentManager::getEnchantment(Enchantment::TOXIC_MIST),
                    EnchantmentManager::getEnchantment(Enchantment::ELEMENTAL_MASTERY),
                    EnchantmentManager::getEnchantment(Enchantment::CACTUS),
                    EnchantmentManager::getEnchantment(Enchantment::INFERNO),
                    EnchantmentManager::getEnchantment(Enchantment::LAST_STAND)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_BOOTS) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "War Boots of the Hero");
                $lore = "A set of gear that can only be wielded by the strongest heroes of this galaxy.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::GEARS),
                    EnchantmentManager::getEnchantment(Enchantment::ELEMENTAL_MASTERY),
                    EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD),
                    EnchantmentManager::getEnchantment(Enchantment::SPRINGS),
                    EnchantmentManager::getEnchantment(Enchantment::ADRENALINE),
                    EnchantmentManager::getEnchantment(Enchantment::BLOOD_MAGIC)
                ];
                $item = $this->enchant($item, $player, $enchantments);
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