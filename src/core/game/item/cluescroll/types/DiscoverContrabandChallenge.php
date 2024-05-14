<?php

declare(strict_types=1);

namespace core\game\item\cluescroll\types;

use core\game\item\cluescroll\Challenge;
use core\game\item\event\DiscoverContrabandEvent;
use core\game\item\event\TinkerEquipmentEvent;
use core\game\item\types\custom\ClueScroll;
use core\game\item\types\custom\Satchel;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use pocketmine\item\Item;

class DiscoverContrabandChallenge extends Challenge {

    /**
     * DiscoverContrabandChallenge constructor.
     *
     * @param int $id
     * @param string $rarity
     */
    public function __construct(int $id, string $rarity) {
        $callable = function(DiscoverContrabandEvent $event, Item $scroll) {
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
                $rarity = $event->getRarity();
                if(Rarity::RARITY_TO_ENCHANTMENT_RARITY_MAP[$rarity] >= Rarity::RARITY_TO_ENCHANTMENT_RARITY_MAP[$this->getRarity()]) {
                    $this->celebrate($player, $scroll, $scrollInstance);
                }
            }
        };
        $description = "Discover a " . $rarity . "+ Contraband from mining Meteor blocks";
        parent::__construct($id, $description, $rarity, self::FIND_CONTRABAND, $callable);
    }
}