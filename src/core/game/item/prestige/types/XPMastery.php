<?php

namespace core\game\item\prestige\types;

use core\game\item\prestige\Prestige;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class XPMastery extends Prestige {

    /**
     * XPMastery constructor.
     */
    public function __construct() {
        parent::__construct("XP Mastery", Pickaxe::XP_MASTERY, 20, 7, 4);
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "+7% - 20% XP";
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return string
     */
    public function getLore(Pickaxe $pickaxe): string {
        $value = $pickaxe->getAttribute($this->getIdentifier());
        return "$value% XP";
    }

    /**
     * @return Item
     */
    public function getDefaultDisplayItem(): Item {
        return VanillaItems::EXPERIENCE_BOTTLE();
    }
}