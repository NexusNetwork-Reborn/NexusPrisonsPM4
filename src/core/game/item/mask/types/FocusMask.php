<?php

namespace core\game\item\mask\types;

use core\game\item\mask\Mask;
use pocketmine\utils\TextFormat;

class FocusMask extends Mask {

    /**
     * GuardMask constructor.
     */
    public function __construct() {
        $description = "Become one with your inner self";
        $abilities = [];
        $abilities[] = "Immune to Shockwave";
        $abilities[] = "+4% Outgoing Damage";
        parent::__construct(self::FOCUS, $description, $abilities);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Focus Mask";
    }
}