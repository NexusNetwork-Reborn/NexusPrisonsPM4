<?php

declare(strict_types = 1);

namespace core\game\kit\types\gkit;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\EnchantmentDust;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\vanilla\Axe;
use core\game\kit\HeroicGodKit;
use core\player\NexusPlayer;
use pocketmine\utils\TextFormat;

class HeroicIapetus extends HeroicGodKit {

    /**
     * Executioner constructor.
     */
    public function __construct() {
        parent::__construct("Heroic Iapetus", TextFormat::BLUE);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::BLUE . "Heroic Iapetus" . TextFormat::RESET;
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
                $item->setOriginalCustomName(TextFormat::RESET . $this->getColoredName() . TextFormat::BLUE . " " . $item->getName());
                $lore = "Forged with the finest materials in the universe.";
                $lore = TextFormat::RESET . TextFormat::ITALIC . $this->color . wordwrap($lore, 25, "\n");
                $item->setOriginalLore([$lore]);
                $enchantments = [
                    EnchantmentManager::getEnchantment(Enchantment::LIGHTNING),
                    EnchantmentManager::getEnchantment(Enchantment::AXEMAN),
                    EnchantmentManager::getEnchantment(Enchantment::BLEED)
                ];
                if($tier >= 2) {
                    $enchantments[] = EnchantmentManager::getEnchantment(Enchantment::WEAKNESS);
                }
                $item = $this->enchant($item, $player, $enchantments);
                $items[] = $item;
            }
        }
        $amount = 8;
        if($tier >= 2) {
            $amount = 16;
        }
        for($i = 0; $i < $amount; $i++) {
            $items[] = (new EnchantmentDust(mt_rand(1, 5 + ($tier * 2))))->toItem()->setCount(1);
        }
        $items[] = (new Energy(mt_rand(200000 + (50000 * $tier), 800000)))->toItem()->setCount(1);
        if(mt_rand(1, 2) === 1) {
            $items[] = (new WhiteScroll())->toItem()->setCount(1);
            if($tier >= 2 and mt_rand($tier, 4) === 1) {
                $items[] = (new WhiteScroll())->toItem()->setCount(1);
            }
        }
        else {
            $items[] = (new BlackScroll(mt_rand(0, 100)))->toItem()->setCount(1);
            if($tier >= 2 and mt_rand($tier, 4) === 1) {
                $items[] = (new BlackScroll(mt_rand(0, 100)))->toItem()->setCount(1);
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