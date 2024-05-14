<?php

declare(strict_types = 1);

namespace core\game\kit\types;

use core\game\item\ItemManager;
use core\game\item\types\custom\BlackScroll;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\GeneratorBooster;
use core\game\item\types\custom\GKitBeacon;
use core\game\item\types\custom\GKitItemGenerator;
use core\game\item\types\custom\OreGenBooster;
use core\game\item\types\custom\RandomGKit;
use core\game\item\types\custom\Shard;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\Rarity;
use core\game\kit\Kit;
use core\player\NexusPlayer;
use pocketmine\utils\TextFormat;

class Heroic extends Kit {

    /**
     * Heroic constructor.
     */
    public function __construct() {
        parent::__construct("Martian+", TextFormat::GOLD, 432000);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::BOLD . TextFormat::YELLOW . "Martian" . TextFormat::GOLD . "+" . TextFormat::RESET;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return array
     */
    public function giveTo(NexusPlayer $player, bool $give = true): array {
        $items = [];
        $level = $player->getDataSession()->getTotalXPLevel();
        $items[] = (new Energy(mt_rand(1500000, 4000000)))->toItem()->setCount(1);
        $rarity = ItemManager::getRarityByLevel($level);
        $items[] = (new Shard($rarity))->toItem()->setCount(mt_rand(64, 128));
        for($i = 0; $i < 3; $i++) {
            switch(mt_rand(1, 4)) {
                case 1:
                    $items[] = (new Contraband(Rarity::ULTIMATE))->toItem()->setCount(1);
                    break;
                case 2:
                    $items[] = (new Contraband(Rarity::LEGENDARY))->toItem()->setCount(1);
                    break;
                case 3:
                    $items[] = (new Contraband(Rarity::GODLY))->toItem()->setCount(1);
                    break;
                case 4:
                    $items[] = (new RandomGKit())->toItem()->setCount(1);
                    break;
            }
        }
        switch(mt_rand(1, 2)) {
            case 1:
                $items[] = (new WhiteScroll())->toItem()->setCount(1);
                break;
            case 2:
                $items[] = (new BlackScroll(mt_rand(1, 100)))->toItem()->setCount(1);
                break;
        }
        $items[] = (new XPBooster(1 + (mt_rand(0, 30) * 0.1), mt_rand(10, 60)))->toItem()->setCount(1);
        $items[] = (new OreGenBooster(mt_rand(15, 300)))->toItem()->setCount(1);
        $items[] = (new EnergyBooster(1 + (mt_rand(0, 30) * 0.1), mt_rand(10, 60)))->toItem()->setCount(1);
        $items[] = (new GKitItemGenerator())->toItem()->setCount(mt_rand(1, 3));
        if($give) {
            foreach($items as $item) {
                if($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                }
                else {
                    if($item->getCount() > 64) {
                        $item->setCount(64);
                    }
                    $player->getWorld()->dropItem($player->getPosition(), $item);
                }
            }
        }
        return $items;
    }
}