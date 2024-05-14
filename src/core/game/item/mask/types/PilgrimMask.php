<?php

namespace core\game\item\mask\types;

use core\game\item\mask\Mask;
use pocketmine\utils\TextFormat;

class PilgrimMask extends Mask {

    /**
     * PilgrimMask constructor.
     */
    public function __construct() {
        $description = "Living off the Land is a lifestyle.";
        $abilities = [];
        $abilities[] = "+50% Shard drop chances";
        parent::__construct(self::PILGRIM, $description, $abilities);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Pilgrim Mask";
    }
}