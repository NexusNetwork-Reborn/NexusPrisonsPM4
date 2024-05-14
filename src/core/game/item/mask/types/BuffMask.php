<?php

namespace core\game\item\mask\types;

use core\game\item\mask\Mask;
use pocketmine\utils\TextFormat;

class BuffMask extends Mask {

    /**
     * GuardMask constructor.
     */
    public function __construct() {
        $description = "Gotta stay tough out in Prison!";
        $abilities = [];
        $abilities[] = "+2 HP";
        $abilities[] = "Immunity to Toxic Mist";
        parent::__construct(self::BUFF, $description, $abilities);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Buff Mask";
    }
}