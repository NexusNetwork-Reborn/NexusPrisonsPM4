<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\block\BlockBreakEvent;

class EnergizeEnchantment extends Enchantment {

    /**
     * EnergizeEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ENERGIZE, "Energize", self::ULTIMATE, "Gain a temporary health boost while mining.", self::BREAK, self::SLOT_PICKAXE, 3);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $random = mt_rand(1, 60);
            $chance = $level * $player->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::ABSORPTION(), 2400, $level - 1, false));
            }
            return;
        };
    }
}