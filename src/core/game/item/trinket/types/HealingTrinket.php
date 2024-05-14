<?php

namespace core\game\item\trinket\types;

use core\game\item\mask\Mask;
use core\game\item\trinket\Trinket;
use core\game\item\types\vanilla\Armor;
use core\player\NexusPlayer;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class HealingTrinket extends Trinket {

    /**
     * HealingTrinket constructor.
     */
    public function __construct() {
        parent::__construct(self::HEALING_TRINKET, "Throws a Potion of Healing II", TextFormat::LIGHT_PURPLE, VanillaItems::STRONG_HEALING_SPLASH_POTION(), 25000, 45);
    }

    /**
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public function onItemUse(NexusPlayer $player): bool {
        $potion = VanillaItems::STRONG_HEALING_SPLASH_POTION();
        $potion->onClickAir($player, $player->getDirectionVector());
        /** @var Armor $helm */
        $helm = $player->getArmorInventory()->getHelmet();
        if($helm instanceof Armor && $helm->hasMask(Mask::CUPID)) {
            $potion = VanillaItems::HEALING_SPLASH_POTION();
            $potion->onClickAir($player, $player->getDirectionVector());
        }
        $player->setCooldown($this->getName());
        return true;
    }
}
