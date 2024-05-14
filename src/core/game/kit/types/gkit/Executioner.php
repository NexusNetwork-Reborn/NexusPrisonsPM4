<?php

declare(strict_types = 1);

namespace core\game\kit\types\gkit;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\kit\GodKit;
use core\player\NexusPlayer;
use pocketmine\utils\TextFormat;

class Executioner extends GodKit {

    /**
     * Executioner constructor.
     */
    public function __construct() {
        parent::__construct("Executioner", TextFormat::DARK_RED);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::DARK_RED . "Executioner" . TextFormat::RESET;
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
            if($item instanceof Axe) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Executioner's Axe");
                $lore = "The original owner was said to be a corrupt executioner, who was stripped of his armor and banished.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::WEAKNESS),
                    EnchantmentManager::getEnchantment(Enchantment::FRENZY),
                    EnchantmentManager::getEnchantment(Enchantment::REJUVENATE),
                    EnchantmentManager::getEnchantment(Enchantment::IMPACT),
                    EnchantmentManager::getEnchantment(Enchantment::DEFLECT),
                    EnchantmentManager::getEnchantment(Enchantment::PUMMEL)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_HEAD) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Executioner's Hood");
                $lore = "The original owner was said to be a corrupt executioner, who was stripped of his armor and banished.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::HARDEN),
                    EnchantmentManager::getEnchantment(Enchantment::EXTINGUISH),
                    EnchantmentManager::getEnchantment(Enchantment::BLOOD_MAGIC)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_CHESTPLATE) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Executioner's Robe");
                $lore = "The original owner was said to be a corrupt executioner, who was stripped of his armor and banished.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::OVERLOAD),
                    EnchantmentManager::getEnchantment(Enchantment::CHIVALRY),
                    EnchantmentManager::getEnchantment(Enchantment::HARDEN),
                    EnchantmentManager::getEnchantment(Enchantment::CURSE),
                    EnchantmentManager::getEnchantment(Enchantment::ESCAPIST),
                    EnchantmentManager::getEnchantment(Enchantment::INFERNO)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_LEGGINGS) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Executioner's Greaves");
                $lore = "The original owner was said to be a corrupt executioner, who was stripped of his armor and banished.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD),
                    EnchantmentManager::getEnchantment(Enchantment::LUCKY),
                    EnchantmentManager::getEnchantment(Enchantment::HARDEN),
                    EnchantmentManager::getEnchantment(Enchantment::CACTUS),
                    EnchantmentManager::getEnchantment(Enchantment::CROUCH),
                    EnchantmentManager::getEnchantment(Enchantment::DEFLECT)
                ];
                $item = $this->enchant($item, $player, $enchantments);
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_BOOTS) {
                $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->color . "Executioner's Sabatons");
                $lore = "The original owner was said to be a corrupt executioner, who was stripped of his armor and banished.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD),
                    EnchantmentManager::getEnchantment(Enchantment::ELEMENTAL_MASTERY),
                    EnchantmentManager::getEnchantment(Enchantment::HARDEN),
                    EnchantmentManager::getEnchantment(Enchantment::SPRINGS)
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