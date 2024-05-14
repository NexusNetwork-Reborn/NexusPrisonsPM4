<?php

namespace core\game\item\mask\types;

use core\game\item\mask\Mask;
use pocketmine\utils\TextFormat;

class JailorMask extends Mask {

    /**
     * GuardMask constructor.
     */
    public function __construct() {
        $description = "Nobody to this date has ever escaped the Jailor";
        $abilities = [];
        $abilities[] = "Immunity to Escapist and Houdini";
        parent::__construct(self::JAILOR, $description, $abilities);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Jailor Mask";
    }
}