<?php

declare(strict_types = 1);

namespace core\game\kit\types\gkit;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\EnchantmentDust;
use core\game\item\types\custom\EnchantmentOrb;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\WhiteScroll;
use core\game\kit\HeroicGodKit;
use core\player\NexusPlayer;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\utils\TextFormat;

class HeroicEnchanter extends HeroicGodKit {

    /**
     * Executioner constructor.
     */
    public function __construct() {
        parent::__construct("Heroic Enchanter", TextFormat::AQUA);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::AQUA . "Heroic Enchanter" . TextFormat::RESET;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return array
     */
    public function giveTo(NexusPlayer $player, bool $give = true): array {
        $items = [];
        $tier = $player->getDataSession()->getGodKitTier($this);
        $enchants = 6 + $tier;
        $rarities = [
            Enchantment::UNCOMMON,
            Enchantment::ELITE,
            Enchantment::ULTIMATE
        ];
        if($player->getDataSession()->getGodKitTier($this) > 1) {
            $rarities[] = Enchantment::LEGENDARY;
        }
        for($i = 0; $i < $enchants; $i++) {
            $rarity = $rarities[array_rand($rarities)];
            if($player->getDataSession()->getGodKitTier($this) >= 3) {
                if(mt_rand(1, 50) === 1) {
                    $rarity = Enchantment::GODLY;
                }
            }
            $enchantment = EnchantmentManager::getRandomMiningEnchantment($rarity);
            $items[] = (new EnchantmentOrb(new EnchantmentInstance($enchantment, mt_rand(1, $enchantment->getMaxLevel())), mt_rand(1, 100)))->toItem()->setCount(1);
        }
        $amount = mt_rand(5, 10 + $tier);
        for($i = 0; $i < $amount; $i++) {
            $items[] = (new EnchantmentDust(mt_rand(1, 5 + ($tier * 2))))->toItem()->setCount(1);
        }
        $items[] = (new Energy(mt_rand(300000 + (100000 * $tier), 900000)))->toItem()->setCount(1);
        if(mt_rand(1, 2) === 1) {
            $items[] = (new WhiteScroll())->toItem()->setCount(1);
        }
        else {
            $items[] = (new BlackScroll(mt_rand(0, 100)))->toItem()->setCount(1);
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