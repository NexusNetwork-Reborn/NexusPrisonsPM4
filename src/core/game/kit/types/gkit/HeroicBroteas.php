<?php

declare(strict_types = 1);

namespace core\game\kit\types\gkit;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\Shard;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Axe;
use core\game\kit\HeroicGodKit;
use core\player\NexusPlayer;
use pocketmine\utils\TextFormat;

class HeroicBroteas extends HeroicGodKit {

    /**
     * Ares constructor.
     */
    public function __construct() {
        parent::__construct("Heroic Broteas", TextFormat::GREEN);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::GREEN . "Heroic Broteas" . TextFormat::RESET;
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
            if($item instanceof Axe) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::GREEN . " " . $item->getName());
                $lore = "Only the true hunter can wield these powerful objects used to cause the demise and sorrow";
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
        }
        $pickaxe = $this->levelToPickaxe($level);
        $enchantments = [
            EnchantmentManager::getEnchantment(Enchantment::MOMENTUM),
            EnchantmentManager::getEnchantment(Enchantment::EFFICIENCY2),
            EnchantmentManager::getEnchantment(Enchantment::ORE_MAGNET),
            EnchantmentManager::getEnchantment(Enchantment::ENERGIZE),
            EnchantmentManager::getEnchantment(Enchantment::ALCHEMY)
        ];
        $pickaxe->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::GREEN . " " . $pickaxe->getName());
        $lore = "Only the true hunter can wield these powerful objects used to cause the demise and sorrow";
        $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
        $pickaxe->setOriginalLore([$lore]);
        $items[] = $this->enchant($pickaxe, $player, $enchantments);
        if($level < 10) {
            $rarity = Rarity::SIMPLE;
        }
        elseif($level < 30) {
            $rarity = Rarity::UNCOMMON;
        }
        elseif($level < 50) {
            $rarity = Rarity::ELITE;
        }
        elseif($level < 70) {
            $rarity = Rarity::ULTIMATE;
        }
        elseif($level < 90) {
            $rarity = Rarity::LEGENDARY;
        }
        else {
            $rarity = Rarity::GODLY;
        }
        $items[] = (new Shard($rarity))->toItem()->setCount(mt_rand(15 + ($tier * 5), 25 + ($tier * 10)));
        $items[] = (new Energy(mt_rand(400000 + ($tier * 50000), 500000 + ($tier * 100000))))->toItem()->setCount(1);
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