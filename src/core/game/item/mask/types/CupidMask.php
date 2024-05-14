<?php

namespace core\game\item\mask\types;

use core\game\item\mask\Mask;
use pocketmine\utils\TextFormat;

class CupidMask extends Mask {

    /**
     * GuardMask constructor.
     */
    public function __construct() {
        $description = "Not even the Wardens can resist your charm with the power of Cupid by your side!";
        $abilities = [];
        $abilities[] = "-50% damage from incoming Consecration";
        $abilities[] = "+2 HP from your own healing trinkets";
        parent::__construct(self::CUPID, $description, $abilities);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Cupid Mask";
    }
}