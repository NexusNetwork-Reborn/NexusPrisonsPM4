<?php

declare(strict_types = 1);

namespace core\game\kit\types\gkit;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\vanilla\Armor;
use core\game\kit\HeroicGodKit;
use core\player\NexusPlayer;
use pocketmine\utils\TextFormat;

class HeroicZenith extends HeroicGodKit {

    /**
     * Ares constructor.
     */
    public function __construct() {
        parent::__construct("Heroic Zenith", TextFormat::GOLD);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::GOLD . "Heroic Zenith" . TextFormat::RESET;
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
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_LEGGINGS) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::GOLD . " " . $item->getName());
                $lore = "Used by miners from the Zenithian mining civilization";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::MANEUVER),
                    EnchantmentManager::getEnchantment(Enchantment::DEFLECT),
                    EnchantmentManager::getEnchantment(Enchantment::LAST_STAND)
                ];
                if($tier >= 2) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::DAMAGE_LIMITER);
                }
                if($tier >= 3) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED);
                }
                $item = $this->enchant($item, $player, $enchantments);
                $items[] = $item;
            }
        }
        $pickaxe = $this->levelToPickaxe($level);
        $enchantments = [
            EnchantmentManager::getEnchantment(Enchantment::EFFICIENCY2),
            EnchantmentManager::getEnchantment(Enchantment::SUPER_BREAKER),
            EnchantmentManager::getEnchantment(Enchantment::ORE_MAGNET),
            EnchantmentManager::getEnchantment(Enchantment::ENRICH)
        ];
        if($tier >= 2) {
            $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::MOMENTUM);
        }
        if($tier >= 3) {
            $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::LUCKY);
        }
        $pickaxe->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::GOLD . " " . $pickaxe->getName());
        $lore = "Used by miners from the Zenithian mining civilization";
        $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
        $pickaxe->setOriginalLore([$lore]);
        $items[] = $this->enchant($pickaxe, $player, $enchantments);
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