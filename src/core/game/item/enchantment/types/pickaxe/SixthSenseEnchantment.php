<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\utils\TextFormat;

class SixthSenseEnchantment extends Enchantment {

    /**
     * SixthSenseEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SIXTH_SENSE, "Sixth Sense", self::SIMPLE, "Senses nearby enemies while mining in unprotected areas.", self::BREAK, self::SLOT_PICKAXE, 1);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($player->getGuardTax() > 0) {
                return;
            }
            $bb = $player->getBoundingBox()->expandedCopy(25, 25, 25);
            $world = $player->getWorld();
            if($world === null) {
                return;
            }
            $gang = $player->getDataSession()->getGang();
            foreach($world->getNearbyEntities($bb) as $e) {
                if($e instanceof NexusPlayer and $e->getUniqueId()->toString() !== $player->getUniqueId()->toString() && !$e->hasVanished()) {
                    if($gang !== null){
                        if($gang->isInGang($e->getName())){
                            continue;
                        }
                        foreach($gang->getAllies() as $ally){
                            $gm = Nexus::getInstance()->getPlayerManager()->getGangManager();
                            $g = $gm->getGang($ally);
                            if($g !== null && $g->isInGang($e->getName())){
                                continue 2;
                            }
                        }
                    }
                    if(!$e->isSurvival(true)) {
                        continue;
                    }
                    $distance = round($player->getPosition()->distance($e->getPosition()), 1);
                    $player->sendAlert(TextFormat::YELLOW . "A player is nearby! They are $distance meters away from you!", 10);
                    break;
                }
            }
            return;
        };
    }
}