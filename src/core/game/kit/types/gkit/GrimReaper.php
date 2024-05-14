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

class GrimReaper extends GodKit {

    /**
     * Ares constructor.
     */
    public function __construct() {
        parent::__construct("Grim Reaper", TextFormat::RED);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::RED . "Grim Reaper" . TextFormat::RESET;
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
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Grim Reaper Scythe");
                $lore = "Used by the lord of death himself, who appears when your time on Nexus has come to an end.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::LIFESTEAL),
                    EnchantmentManager::getEnchantment(Enchantment::FRENZY),
                    EnchantmentManager::getEnchantment(Enchantment::LUCKY),
                    EnchantmentManager::getEnchantment(Enchantment::HOOK),
                    EnchantmentManager::getEnchantment(Enchantment::DEMON_FORGED),
                    EnchantmentManager::getEnchantment(Enchantment::TRAP),
                    EnchantmentManager::getEnchantment(Enchantment::FROSTBLADE)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_HEAD) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Grim Reaper Hood");
                $lore = "Used by the lord of death himself, who appears when your time on Nexus has come to an end.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::ELEMENTAL_MASTERY),
                    EnchantmentManager::getEnchantment(Enchantment::CHIVALRY),
                    EnchantmentManager::getEnchantment(Enchantment::BLOOD_MAGIC),
                    EnchantmentManager::getEnchantment(Enchantment::EXTINGUISH),
                    EnchantmentManager::getEnchantment(Enchantment::TOXIC_MIST)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_CHESTPLATE) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Mantel of the Reaper");
                $lore = "Used by the lord of death himself, who appears when your time on Nexus has come to an end.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::AEGIS),
                    EnchantmentManager::getEnchantment(Enchantment::CURSE),
                    EnchantmentManager::getEnchantment(Enchantment::INFERNO)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_LEGGINGS) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Tattered Robes of the Reaper");
                $lore = "Used by the lord of death himself, who appears when your time on Nexus has come to an end.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::CACTUS),
                    EnchantmentManager::getEnchantment(Enchantment::CROUCH)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_BOOTS) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Sandals of the Reaper");
                $lore = "Used by the lord of death himself, who appears when your time on Nexus has come to an end.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::GEARS),
                    EnchantmentManager::getEnchantment(Enchantment::HARDEN),
                    EnchantmentManager::getEnchantment(Enchantment::ESCAPIST),
                    EnchantmentManager::getEnchantment(Enchantment::SPRINGS),
                    EnchantmentManager::getEnchantment(Enchantment::ADRENALINE),
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