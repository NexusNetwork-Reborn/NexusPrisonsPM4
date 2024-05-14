<?php

declare(strict_types = 1);

namespace core\game\kit\types;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\types\custom\Shard;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Sword;
use core\game\kit\Kit;
use core\player\NexusPlayer;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\utils\TextFormat;

class Imperial extends Kit {

    /**
     * Archer constructor.
     */
    public function __construct() {
        parent::__construct("Divergent", TextFormat::LIGHT_PURPLE, 216000);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Divergent" . TextFormat::RESET;
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
            if($item instanceof Armor) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::WHITE . " " . $item->getName());
                $item->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::MANEUVER), 2));
                $item->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::UNKNOWN), 1));
                $items[] = $item;
            }
            if($item instanceof Sword) {
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::WHITE . " " . $item->getName());
                $item->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::POISON), 2));
                $items[] = $item;
            }
        }
        $rarity = ItemManager::getRarityByLevel($level);
        $items[] = (new Shard($rarity))->toItem()->setCount(mt_rand(10, 15));
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