<?php

namespace core\game\item\types;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\vanilla\Fireworks;
use pocketmine\utils\TextFormat;

class Rarity {

    const RARITY_TO_COLOR_MAP = [
        self::SIMPLE => TextFormat::DARK_GRAY,
        self::UNCOMMON => TextFormat::GREEN,
        self::ELITE => TextFormat::BLUE,
        self::ULTIMATE => TextFormat::YELLOW,
        self::LEGENDARY => TextFormat::GOLD,
        self::GODLY => TextFormat::RED,
        self::EXECUTIVE => TextFormat::LIGHT_PURPLE,
        self::ENERGY => TextFormat::DARK_AQUA
    ];

    const ENCHANTMENT_RARITY_TO_STRING_MAP = [
        Enchantment::SIMPLE => self::SIMPLE,
        Enchantment::UNCOMMON => self::UNCOMMON,
        Enchantment::ELITE => self::ELITE,
        Enchantment::ULTIMATE => self::ULTIMATE,
        Enchantment::LEGENDARY => self::LEGENDARY,
        Enchantment::GODLY => self::GODLY,
        Enchantment::EXECUTIVE => self::EXECUTIVE,
        Enchantment::ENERGY => self::ENERGY
    ];

    const RARITY_TO_ENCHANTMENT_RARITY_MAP = [
        self::SIMPLE => Enchantment::SIMPLE,
        self::UNCOMMON => Enchantment::UNCOMMON,
        self::ELITE => Enchantment::ELITE,
        self::ULTIMATE => Enchantment::ULTIMATE,
        self::LEGENDARY => Enchantment::LEGENDARY,
        self::GODLY => Enchantment::GODLY,
        self::EXECUTIVE => Enchantment::EXECUTIVE,
        self::ENERGY => Enchantment::ENERGY
    ];

    const SIMPLE = "Simple";

    const UNCOMMON = "Uncommon";

    const ELITE = "Elite";

    const ULTIMATE = "Ultimate";

    const LEGENDARY = "Legendary";

    const GODLY = "Godly";

    const EXECUTIVE = "Executive";

    const ENERGY = "Energy";

    /**
     * @param string $rarity
     *
     * @return float
     */
    public static function getCrudeOreMultiplier(string $rarity): float {
        switch($rarity) {
            case self::UNCOMMON:
                return 1.2;
                break;
            case self::ELITE:
                return 1.4;
                break;
            case self::ULTIMATE:
                return 1.6;
                break;
            case self::LEGENDARY:
                return 1.8;
                break;
            case self::GODLY:
                return 2.0;
                break;
            default:
                return 1.0;
                break;
        }
    }

    /**
     * @param string $rarity
     *
     * @return string
     */
    public static function getFireworkColor(string $rarity): string {
        switch($rarity) {
            case self::UNCOMMON:
                return Fireworks::COLOR_GREEN;
                break;
            case self::ELITE:
                return Fireworks::COLOR_BLUE;
                break;
            case self::ULTIMATE:
                return Fireworks::COLOR_YELLOW;
                break;
            case self::LEGENDARY:
                return Fireworks::COLOR_GOLD;
                break;
            case self::GODLY:
                return Fireworks::COLOR_RED;
                break;
            default:
                return Fireworks::COLOR_GRAY;
                break;
        }
    }
}