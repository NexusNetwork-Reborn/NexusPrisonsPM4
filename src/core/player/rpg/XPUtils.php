<?php
declare(strict_types=1);

namespace core\player\rpg;

class XPUtils {

    /**
     * @param int $xp
     * @param int $prestige
     * @param float|int $modifier
     *
     * @return int
     */
    public static function getNeededXP(int|float $xp, int $prestige = 0, float $modifier = RPGManager::MODIFIER): int {
        $xp = (int) $xp;
        $currentLevelXP = self::xpToLevel($xp, $modifier);
        $nextLevelXP = self::levelToXP($currentLevelXP + 1, $modifier);
        if($modifier === RPGManager::MODIFIER) {
            if($currentLevelXP >= RPGManager::MAX_LEVEL) {
                $nextLevelXP = self::levelToXP(RPGManager::MAX_LEVEL, $modifier) + RPGManager::getPrestigeXP($prestige);
            }
        }
        $currentLevelXP = self::levelToXP($currentLevelXP, $modifier);
        $neededXP = $nextLevelXP - $currentLevelXP;
        $earnedXP = $xp - $currentLevelXP;
        return ($neededXP - $earnedXP) >= 0 ? ($neededXP - $earnedXP) : 0;
    }

    /**
     * @param int $xp
     * @param float $modifier
     *
     * @return int
     */
    public static function xpToLevel(int|float $xp, float $modifier = RPGManager::MODIFIER): int {
        if(is_float($xp)){
            $xp = (int) $xp;
        }

        if($modifier === RPGManager::MODIFIER) {
            return min(RPGManager::MAX_LEVEL, (int)($modifier * sqrt($xp)));
        }
        return (int)($modifier * sqrt($xp));
    }

    /**
     * @param int $level
     * @param float $modifier
     *
     * @return int
     */
    public static function levelToXP(int|float $level, float $modifier = RPGManager::MODIFIER): int {
        if(is_float($level)){
            $level = (int) $level;
        }

        if($modifier === RPGManager::MODIFIER) {
            $level = min(RPGManager::MAX_LEVEL, $level);
        }
        return (int)($level / $modifier) ** 2;
    }

    /**
     * @param int $xp
     * @param int $prestige
     * @param float|int $modifier
     *
     * @return int
     */
    public static function getProgressXP(int|float $xp, int $prestige = 0, float $modifier = RPGManager::MODIFIER): int {
        $xp = (int) $xp;

        $currentLevel = self::xpToLevel($xp, $modifier);
        $currentLevelXP = self::levelToXP($currentLevel, $modifier);
        if($modifier === RPGManager::MODIFIER) {
            if($currentLevel >= RPGManager::MAX_LEVEL) {
                $currentLevelXP = self::levelToXP(RPGManager::MAX_LEVEL, $modifier) + RPGManager::getPrestigeXP($prestige);
            }
        }
        return ($xp - $currentLevelXP) >= 0 ? ($xp - $currentLevelXP) : 0;
    }

    /**
     * @param int $xp
     * @param int $prestige
     *
     * @return int
     */
    public static function getMaxSubtractableXP(int|float $xp, int $prestige = 0): int {
        $xp = (int) $xp;

        $currentLevel = self::xpToLevel($xp, RPGManager::MODIFIER);
        $currentLevelXP = self::levelToXP(self::getMaxSubtractableLevels($xp));
        if($currentLevel >= RPGManager::MAX_LEVEL) {
            $currentLevelXP = self::levelToXP(RPGManager::MAX_LEVEL, RPGManager::MODIFIER) + RPGManager::getPrestigeXP($prestige);
        }
        return ($xp - $currentLevelXP) >= 0 ? ($xp - $currentLevelXP) : 0;
    }

    /**
     * @param int $xp
     *
     * @return int
     */
    public static function getMaxSubtractableLevels(int $xp): int {
        $currentLevel = self::xpToLevel($xp, RPGManager::MODIFIER);
        if($currentLevel >= 90) {
            return 90;
        }
        if($currentLevel >= 70) {
            return 70;
        }
        if($currentLevel >= 50) {
            return 50;
        }
        if($currentLevel >= 30) {
            return 30;
        }
        if($currentLevel >= 10) {
            return 10;
        }
        return 0;
    }

    /**
     * @param int $xp
     * @param int $prestige
     * @param float $modifier
     * @param int $precision
     *
     * @return float
     */
    public static function getXPProgress(int|float $xp, int $prestige = 0, float $modifier = RPGManager::MODIFIER, int $precision = 0): float {
        $xp = (int) $xp;
        $currentLevel = self::xpToLevel($xp, $modifier);
        $nextLevelXP = self::levelToXP($currentLevel + 1, $modifier);
        if($modifier === RPGManager::MODIFIER) {
            if($currentLevel >= RPGManager::MAX_LEVEL) {
                $nextLevelXP = self::levelToXP(RPGManager::MAX_LEVEL, $modifier) + RPGManager::getPrestigeXP($prestige);
            }
        }
        $currentLevelXP = self::levelToXP($currentLevel, $modifier);
        $neededXP = $nextLevelXP - $currentLevelXP;
        $earnedXP = $xp - $currentLevelXP;
        return min(100, round(($earnedXP / $neededXP) * 100, $precision) >= 0 ? round(($earnedXP / $neededXP) * 100, $precision) : 0);
    }
}