<?php
declare(strict_types=1);

namespace core\game\item\event;

use core\game\item\types\vanilla\Pickaxe;
use pocketmine\event\player\PlayerEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class LevelUpPickaxeEvent extends PlayerEvent {

    /** @var Pickaxe */
    private $item;

    /**
     * LevelUpPickaxeEvent constructor.
     *
     * @param Player $player
     * @param Pickaxe $item
     */
    public function __construct(Player $player, Pickaxe $item) {
        $this->player = $player;
        $this->item = $item;
    }

    /**
     * @return Pickaxe
     */
    public function getItem(): Pickaxe {
        return $this->item;
    }
}