<?php

namespace core\game\item\mask\types;

use core\game\item\mask\Mask;
use pocketmine\utils\TextFormat;

class ShadowMask extends Mask {

    /**
     * GuardMask constructor.
     */
    public function __construct() {
        $description = "Nothing is more deadly than someone who is trained in being one with the shadows...";
        $abilities = [];
        $abilities[] = "2% Dodge Chance";
        $abilities[] = "+15% Movement Speed after dodging (8s)";
        parent::__construct(self::SHADOW, $description, $abilities);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_GRAY . "Shadow Mask";
    }
}
