<?php

namespace core\game\item\trinket\types;

use core\game\item\trinket\entity\AbsorptionPotion;
use core\game\item\trinket\Trinket;
use core\player\NexusPlayer;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ThrowSound;

class AbsorptionTrinket extends Trinket {

    /**
     * HealingTrinket constructor.
     */
    public function __construct() {
        parent::__construct(self::ABSORPTION_TRINKET, "Throws a Potion of Absorption II", TextFormat::YELLOW, VanillaItems::FIRE_RESISTANCE_SPLASH_POTION(), 40000, 60);
    }

    /**
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public function onItemUse(NexusPlayer $player): bool {
        $directionVector = $player->getDirectionVector();
        $location = $player->getLocation();
        $potion = new AbsorptionPotion(Location::fromObject($player->getEyePos(), $player->getWorld(), $location->yaw, $location->pitch), $player);
        $potion->setMotion($directionVector->multiply(0.5));
        $potion->spawnToAll();
        $location->getWorld()->addSound($location, new ThrowSound());
        $player->setCooldown($this->getName());
        return true;
    }
}
