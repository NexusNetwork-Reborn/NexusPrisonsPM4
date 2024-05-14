<?php

namespace core\game\item\trinket\types;

use core\game\item\trinket\entity\AbsorptionPotion;
use core\game\item\trinket\entity\ResistancePotion;
use core\game\item\trinket\Trinket;
use core\player\NexusPlayer;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ThrowSound;

class ResistanceTrinket extends Trinket {

    /**
     * ResistanceTrinket constructor.
     */
    public function __construct() {
        parent::__construct(self::RESISTANCE_TRINKET, "Throws a Potion of Resistance II", TextFormat::DARK_PURPLE, VanillaItems::HARMING_SPLASH_POTION(), 65000, 120);
    }

    /**
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public function onItemUse(NexusPlayer $player): bool {
        $directionVector = $player->getDirectionVector();
        $location = $player->getLocation();
        $potion = new ResistancePotion(Location::fromObject($player->getEyePos(), $player->getWorld(), $location->yaw, $location->pitch), $player);
        $potion->setMotion($directionVector->multiply(0.5));
        $potion->spawnToAll();
        $location->getWorld()->addSound($location, new ThrowSound());
        $player->setCooldown($this->getName());
        return true;
    }
}
