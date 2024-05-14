<?php

namespace core\game\item\mask\types;

use core\game\item\mask\Mask;
use pocketmine\utils\TextFormat;

class GuardMask extends Mask {

    /**
     * GuardMask constructor.
     */
    public function __construct() {
        $description = "Proud to serve and protect the galaxy from those who choose to ignore the laws!";
        $abilities = [];
        $abilities[] = "+10% XP";
        $abilities[] = "+10% Sell Prices";
        parent::__construct(self::GUARD, $description, $abilities);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Guard Mask";
    }
}