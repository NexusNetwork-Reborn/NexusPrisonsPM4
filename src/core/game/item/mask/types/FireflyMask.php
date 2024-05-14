<?php

namespace core\game\item\mask\types;

use core\game\item\mask\Mask;
use pocketmine\utils\TextFormat;

class FireflyMask extends Mask {

    /**
     * GuardMask constructor.
     */
    public function __construct() {
        $description = "With the ability to guide you through the night or hide you in plain sight";
        $abilities = [];
        $abilities[] = "3% Chance for Invisibility for 3 seconds when below 50% HP";
        $abilities[] = "Immunity to Lightning";
        parent::__construct(self::FIREFLY, $description, $abilities);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Firefly Mask";
    }
}