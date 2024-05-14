<?php

namespace core\game\item\mask\types;

use core\game\item\mask\Mask;
use pocketmine\utils\TextFormat;

class CactusMask extends Mask {

    /**
     * GuardMask constructor.
     */
    public function __construct() {
        $description = "The type of plant that only its mother could love.";
        $abilities = [];
        $abilities[] = "5% chance to damage attackers for 1 HP";
        parent::__construct(self::CACTUS, $description, $abilities);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Cactus Mask";
    }
}