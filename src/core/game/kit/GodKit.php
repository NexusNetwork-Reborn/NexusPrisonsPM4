<?php

declare(strict_types = 1);

namespace core\game\kit;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

abstract class GodKit extends Kit {

    /** @var int */
    protected $maxTier = 3;

    /**
     * GodKit constructor.
     *
     * @param string $name
     * @param string $color
     * @param int $cooldown
     */
    public function __construct(string $name, string $color, int $cooldown = 432000) {
        parent::__construct($name, $color, $cooldown);
    }

    /**
     * @return int
     */
    public function getMaxTier(): int {
        return $this->maxTier;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return int
     */
    public function getCooldownFor(NexusPlayer $player): int {
        return max(0, $this->cooldown - (18000 * $player->getDataSession()->getGodKitTier($this)));
    }

    /**
     * @param Item $item
     * @param NexusPlayer $player
     * @param array $enchantments
     * @param Enchantment|null $chanceEnchantment
     * @param int $chanceEnchant
     *
     * @return Item
     */
    public function enchant(Item $item, NexusPlayer $player, array $enchantments, ?Enchantment $chanceEnchantment = null, int $chanceEnchant = 5): Item {
        if($item instanceof Armor or $item instanceof Sword or $item instanceof Axe or $item instanceof Bow) {
            shuffle($enchantments);
            switch($player->getDataSession()->getGodKitTier($this)) {
                case 1:
                    $chance = 30;
                    break;
                case 2:
                    $chance = 50;
                    break;
                case 3:
                    $chance = 80;
                    break;
                default:
                    $chance = 10;
                    break;
            }
            $levels = 0;
            foreach($enchantments as $enchantment) {
                $levels += $enchantment->getMaxLevel();
            }
            if($item->getMaxLevel() > $levels) {
                $max = mt_rand((int)($levels * ($chance / 100)), $levels);
                if($chance >= mt_rand(1, 100)) {
                    $max = $item->getMaxLevel();
                }
            }
            else {
                $max = mt_rand((int)($item->getMaxLevel() * ($chance / 100)), $item->getMaxLevel());
                if($chance >= mt_rand(1, 100)) {
                    $max = $item->getMaxLevel();
                }
            }
            if($levels <= $max) {
                foreach($enchantments as $enchantment) {
                    $instance = new EnchantmentInstance($enchantment, $enchantment->getMaxLevel());
                    $item->addEnchantment($instance);
                }
                $max -= $levels;
            }
            else {
                foreach($enchantments as $enchantment) {
                    if($max > 0) {
                        $maxLevel = $max;
                        if($enchantment->getMaxLevel() < $max) {
                            $maxLevel = $enchantment->getMaxLevel();
                        }
                        $start = 1;
                        if($max > $maxLevel) {
                            $start = $maxLevel - 1;
                        }
                        $level = mt_rand($start, $maxLevel);
                        $max -= $level;
                        $instance = new EnchantmentInstance($enchantment, $level);
                        $item->addEnchantment($instance);
                    }
                }
            }
            if($chanceEnchantment !== null) {
                if($max >= 1) {
                    if(mt_rand(1, $chanceEnchant) === 1) {
                        $instance = new EnchantmentInstance($chanceEnchantment, 1);
                        $item->addEnchantment($instance);
                    }
                }
            }
        }
        if($item instanceof Pickaxe) {
            switch($player->getDataSession()->getGodKitTier($this)) {
                case 1:
                    $chance = 30;
                    break;
                case 2:
                    $chance = 50;
                    break;
                case 3:
                    $chance = 80;
                    break;
                default:
                    $chance = 10;
                    break;
            }
            $levels = 0;
            foreach($enchantments as $enchantment) {
                $levels += $enchantment->getMaxLevel();
            }
            $max = mt_rand((int)($levels * ($chance / 100)), $levels);
            if($chance >= mt_rand(1, 100)) {
                $max = $levels;
            }
            if($levels < $max) {
                foreach($enchantments as $enchantment) {
                    $instance = new EnchantmentInstance($enchantment, $enchantment->getMaxLevel());
                    $item->addEnchantment($instance);
                }
                $max -= $levels;
            }
            else {
                foreach($enchantments as $enchantment) {
                    if($max > 0) {
                        $maxLevel = $max;
                        if($enchantment->getMaxLevel() < $max) {
                            $maxLevel = $enchantment->getMaxLevel();
                        }
                        $start = 1;
                        $level = mt_rand($start, $maxLevel);
                        $max -= $level;
                        $instance = new EnchantmentInstance($enchantment, $level);
                        $item->addEnchantment($instance);
                    }
                }
            }
            if($chanceEnchantment !== null) {
                if($max >= 1) {
                    if(mt_rand(1, $chanceEnchant) === 1) {
                        $instance = new EnchantmentInstance($chanceEnchantment, 1);
                        $item->addEnchantment($instance);
                    }
                }
            }
            $levels = 0;
            foreach($item->getEnchantments() as $enchantment) {
                $levels += $enchantment->getLevel();
            }
            $item->addEnergy(XPUtils::levelToXP($levels, RPGManager::ENERGY_MODIFIER));
            $item->subtractPoints($item->getPoints());
        }
        return $item;
    }
}