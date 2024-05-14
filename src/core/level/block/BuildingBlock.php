<?php

namespace core\level\block;

use core\player\NexusPlayer;
use pocketmine\block\Opaque;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BuildingBlock extends Opaque {

    public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null): bool
    {
        if($player->getWorld()->getFolderName() === "executive") {
            if($player instanceof NexusPlayer) {
                $player->sendAlert(TextFormat::YELLOW . TextFormat::BOLD . "Building Blocks\n" . TextFormat::RESET . "When mined these blocks can be placed\nfor a limited time, to access deeper locations in the mine,\nwhere you can find more " . TextFormat::BLUE . "Prismarine" . TextFormat::RESET . " and the " . TextFormat::DARK_RED  . TextFormat::BOLD . "Executive Wormhole!");
            }
        }
        return true;
    }

}