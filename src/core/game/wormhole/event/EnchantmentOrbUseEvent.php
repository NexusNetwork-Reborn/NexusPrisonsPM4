<?php
declare(strict_types=1);

namespace core\game\wormhole\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\player\Player;

class EnchantmentOrbUseEvent extends PlayerEvent {

    /** @var EnchantmentInstance */
    private $enchantment;

    /**
     * EnchantmentOrbUseEvent constructor.
     *
     * @param Player $player
     * @param EnchantmentInstance $enchantment
     */
    public function __construct(Player $player, EnchantmentInstance $enchantment) {
        $this->player = $player;
        $this->enchantment = $enchantment;
    }

    /**
     * @return EnchantmentInstance
     */
    public function getEnchantment(): EnchantmentInstance {
        return $this->enchantment;
    }
}