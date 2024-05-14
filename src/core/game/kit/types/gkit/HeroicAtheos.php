<?php

declare(strict_types = 1);

namespace core\game\kit\types\gkit;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\Token;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Sword;
use core\game\kit\HeroicGodKit;
use core\player\NexusPlayer;
use pocketmine\utils\TextFormat;

class HeroicAtheos extends HeroicGodKit {

    /**
     * Ares constructor.
     */
    public function __construct() {
        parent::__construct("Heroic Atheos", TextFormat::GRAY);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::GRAY . "Heroic Atheos" . TextFormat::RESET;
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
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::GRAY . " " . $item->getName());
                $lore = "I sense a lifeless energy in this item...";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::LIGHTNING),
                    EnchantmentManager::getEnchantment(Enchantment::LIFESTEAL),
                    EnchantmentManager::getEnchantment(Enchantment::FEARLESS),
                    EnchantmentManager::getEnchantment(Enchantment::HOOK),
                    EnchantmentManager::getEnchantment(Enchantment::FLING),
                    EnchantmentManager::getEnchantment(Enchantment::FAMINE)
                ];
                $item = $this->enchant($item, $player, $enchantments);
                $items[] = $item;
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_BOOTS) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::GRAY . " " . $item->getName());
                $lore = "I sense a lifeless energy in this item...";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::MANEUVER),
                    EnchantmentManager::getEnchantment(Enchantment::CROUCH),
                    EnchantmentManager::getEnchantment(Enchantment::DAMAGE_LIMITER)
                ];
                if($tier >= 2) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED);
                }
                if($tier >= 3) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD);
                }
                $item = $this->enchant($item, $player, $enchantments);
                $items[] = $item;
            }
        }
        $min = 10 + (3 * $tier);
        $max = 15 + (3 * $tier);
        $items[] = (new Token())->toItem()->setCount(mt_rand($min, $max));
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