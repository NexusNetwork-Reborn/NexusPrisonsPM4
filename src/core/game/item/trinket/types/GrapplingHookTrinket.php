<?php

namespace core\game\item\trinket\types;

use core\game\item\trinket\entity\GrapplingHook;
use core\game\item\trinket\Trinket;
use core\player\NexusPlayer;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ThrowSound;

class GrapplingHookTrinket extends Trinket {

    /**
     * HealingTrinket constructor.
     */
    public function __construct() {
        parent::__construct(self::GRAPPLING_TRINKET, "Throws a Grappling Hook that you can jump to", TextFormat::GRAY, VanillaItems::FISHING_ROD(), 50000, 40);
    }

    /**
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public function onItemUse(NexusPlayer $player): bool {
        $location = $player->getLocation();
        if($player->getGrapplingHook() === null) {
            $hook = new GrapplingHook($location, $player);
            $hook->spawnToAll();
            $location->getWorld()->addSound($location, new ThrowSound());
            $player->broadcastAnimation(new ArmSwingAnimation($player));
            return false;
        }
        elseif($player->getGrapplingHook()->isOnGround()) {
            $hook = $player->getGrapplingHook();
            $hook->handleHookRetraction();
            $player->setCooldown($this->getName());
        }
        $player->broadcastAnimation(new ArmSwingAnimation($player));
        return true;
    }
}
