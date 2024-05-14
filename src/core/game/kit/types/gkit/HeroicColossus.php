<?php

declare(strict_types = 1);

namespace core\game\kit\types\gkit;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Sword;
use core\game\kit\HeroicGodKit;
use core\player\NexusPlayer;
use pocketmine\utils\TextFormat;

class HeroicColossus extends HeroicGodKit {

    /**
     * Ares constructor.
     */
    public function __construct() {
        parent::__construct("Heroic Colossus", TextFormat::WHITE);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::WHITE . "Heroic Colossus" . TextFormat::RESET;
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
        $tier = $player->getDataSession()->getGodKitTier($this);
        foreach($set as $item) {
            if($item instanceof Sword) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::WHITE . " " . $item->getName());
                $lore = "Infused with an unstoppable force by an unknown entity";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::LIGHTNING),
                    EnchantmentManager::getEnchantment(Enchantment::PUMMEL),
                    EnchantmentManager::getEnchantment(Enchantment::DEMON_FORGED),
                    EnchantmentManager::getEnchantment(Enchantment::SCORCH),
                    EnchantmentManager::getEnchantment(Enchantment::FAMINE)
                ];
                if($tier >= 2) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::FRENZY);
                }
                $item = $this->enchant($item, $player, $enchantments);
                $items[] = $item;
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_BOOTS) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::WHITE . " " . $item->getName());
                $lore = "Infused with an unstoppable force by an unknown entity";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::GEARS),
                    EnchantmentManager::getEnchantment(Enchantment::SPRINGS),
                    EnchantmentManager::getEnchantment(Enchantment::ADRENALINE),
                    EnchantmentManager::getEnchantment(Enchantment::DEFLECT),
                    EnchantmentManager::getEnchantment(Enchantment::TOXIC_MIST)
                ];
                if($tier >= 2) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD);
                }
                $item = $this->enchant($item, $player, $enchantments);
                $items[] = $item;
            }
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