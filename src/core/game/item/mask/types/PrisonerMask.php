<?php

namespace core\game\item\mask\types;

use core\game\item\mask\Mask;
use pocketmine\utils\TextFormat;

class PrisonerMask extends Mask {

    /**
     * GuardMask constructor.
     */
    public function __construct() {
        $description = "Serving time for a crime he claims he never committed. Now fueled with anger he promises he will one day get his vengeance!";
        $abilities = [];
        $abilities[] = "+5% Damage";
        parent::__construct(self::PRISONER, $description, $abilities);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Prisoner Mask";
    }
}