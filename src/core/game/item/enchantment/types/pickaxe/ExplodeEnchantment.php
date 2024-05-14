<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\PickaxeExplosion;
use core\player\NexusPlayer;
use pocketmine\event\block\BlockBreakEvent;

class ExplodeEnchantment extends Enchantment {

    /**
     * ExplodeEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::EXPLODE, "Explode", self::LEGENDARY, "Chance to cause an explosion which mines up nearby ores.", self::BREAK, self::SLOT_PICKAXE, 6);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($player->getCESession()->hasExplode()) {
                return;
            }
            $random = mt_rand(1, 3000);
            $chance = $level * $player->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $size = (2 + ($level * 0.75)) / 2;
                $explosion = new PickaxeExplosion($event->getBlock()->getPosition(), $size, $player);
                $explosion->explodeA();
                $explosion->explodeB();
            }
        };
    }
}