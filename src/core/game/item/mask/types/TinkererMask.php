<?php

namespace core\game\item\mask\types;

use core\game\item\mask\Mask;
use pocketmine\utils\TextFormat;

class TinkererMask extends Mask {

    /**
     * GuardMask constructor.
     */
    public function __construct() {
        $description = "Extract the powers of the legendary Timmy the Tinkerer!";
        $abilities = [];
        $abilities[] = "+10% Energy Gain in Overworld (+5% anywhere else)";
        $abilities[] = "No /extract fee";
        parent::__construct(self::TINKERER, $description, $abilities);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Tinkerer Mask";
    }
}