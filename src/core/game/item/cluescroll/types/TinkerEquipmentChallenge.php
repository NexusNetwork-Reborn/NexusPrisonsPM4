<?php

declare(strict_types=1);

namespace core\game\item\cluescroll\types;

use core\game\item\cluescroll\Challenge;
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

class TinkerEquipmentChallenge extends Challenge {
    
    const RARITY_TO_LEVEL = [
        Rarity::SIMPLE => 5,
        Rarity::UNCOMMON => 10,
        Rarity::ELITE => 15,
        Rarity::ULTIMATE => 20,
        Rarity::LEGENDARY => 25,
        Rarity::GODLY => 30
    ];

    /**
     * TinkerEquipmentChallenge constructor.
     *
     * @param int $id
     * @param string $rarity
     */
    public function __construct(int $id, string $rarity) {
        $callable = function(TinkerEquipmentEvent $event, Item $scroll) {
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
                $item = $event->getItem();
                $levels = 0;
                $requiredLevels = self::RARITY_TO_LEVEL[$this->rarity];
                if($item instanceof Pickaxe) {
                    $levels = XPUtils::xpToLevel($item->getEnergy(), RPGManager::ENERGY_MODIFIER);
                }
                if($item instanceof Armor or $item instanceof Pickaxe or $item instanceof Axe or $item instanceof Bow or $item instanceof Sword) {
                    foreach($item->getEnchantments() as $ei) {
                        $levels += $ei->getLevel();
                    }
                }
                if(Satchel::isInstanceOf($item)) {
                    $satchel = Satchel::fromItem($item);
                    $levels = XPUtils::xpToLevel($satchel->getEnergy(), RPGManager::SATCHEL_MODIFIER);
                }
                if($levels >= $requiredLevels) {
                    $this->celebrate($player, $scroll, $scrollInstance);
                }
            }
        };
        $level = self::RARITY_TO_LEVEL[$rarity];
        $description = "Tinker a level " . $level . "+ Tool or Armor";
        parent::__construct($id, $description, $rarity, self::TINKER_EQUIPMENT, $callable);
    }
}