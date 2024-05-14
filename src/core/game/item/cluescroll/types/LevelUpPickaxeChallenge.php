<?php

declare(strict_types=1);

namespace core\game\item\cluescroll\types;

use core\game\item\cluescroll\Challenge;
use core\game\item\event\LevelUpPickaxeEvent;
use core\game\item\ItemManager;
use core\game\item\types\custom\ClueScroll;
use core\player\NexusPlayer;
use pocketmine\item\Item;

class LevelUpPickaxeChallenge extends Challenge {

    /**
     * LevelUpPickaxeChallenge constructor.
     *
     * @param int $id
     * @param string $rarity
     */
    public function __construct(int $id, string $rarity) {
        $callable = function(LevelUpPickaxeEvent $event, Item $scroll) {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $scrollInstance = ClueScroll::fromItem($scroll);
            if($scrollInstance === null) {
                return;
            }
            $challenge = $scrollInstance->getCurrentChallenge();
            if($challenge === $this->getId()) {
                $pickaxe = $event->getItem();
                $tier = ItemManager::getToolTierByRarity($this->getRarity());
                if($pickaxe->getBlockToolHarvestLevel() >= $tier->getHarvestLevel()) {
                    $this->celebrate($player, $scroll, $scrollInstance);
                }
            }
        };
        $tier = ItemManager::getToolTierByRarity($this->getRarity());
        $description = "Level up a " . ucfirst($tier->name()) . " Pickaxe";
        parent::__construct($id, $description, $rarity, self::LEVEL_UP_PICKAXE, $callable);
    }
}