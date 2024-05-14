<?php

declare(strict_types = 1);

namespace core\game\kit\types\gkit;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\SlaughterLootbag;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\kit\HeroicGodKit;
use core\player\NexusPlayer;
use pocketmine\utils\TextFormat;

class HeroicSlaughter extends HeroicGodKit {

    /**
     * Executioner constructor.
     */
    public function __construct() {
        parent::__construct("Heroic Slaughter", TextFormat::RED);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::RED . "Heroic Slaughter" . TextFormat::RESET;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return array
     */
    public function giveTo(NexusPlayer $player, bool $give = true): array {
        $items = [];
        $level = $player->getDataSession()->getTotalXPLevel();
        $set = $this->levelToArmorSetAxe($level);
        $tier = $player->getDataSession()->getGodKitTier($this);
        foreach($set as $item) {
            if($item instanceof Axe) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::RED . " " . $item->getName());
                $lore = "Shadows of a thousand years rise again unseen, Voices whisper in the trees, 'Tonight is Halloween!'";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::FAMINE),
                    EnchantmentManager::getEnchantment(Enchantment::CANNIBALISM),
                    EnchantmentManager::getEnchantment(Enchantment::FRENZY)
                ];
                if($tier >= 1) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::TRAP);
                }
                if($tier >= 2) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::WEAKNESS);
                }
                $item = $this->enchant($item, $player, $enchantments);
                $items[] = $item;
            }
            if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_CHESTPLATE) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::RED . " " . $item->getName());
                $lore = "Shadows of a thousand years rise again unseen, Voices whisper in the trees, 'Tonight is Halloween!'";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::OVERLOAD),
                    EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED),
                    EnchantmentManager::getEnchantment(Enchantment::DEFLECT)
                ];
                if($tier >= 1) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD);
                }
                if($tier >= 2) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::DAMAGE_LIMITER);
                }
                $item = $this->enchant($item, $player, $enchantments);
                $items[] = $item;
            }
        }
        $items[] = (new SlaughterLootbag())->toItem();
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