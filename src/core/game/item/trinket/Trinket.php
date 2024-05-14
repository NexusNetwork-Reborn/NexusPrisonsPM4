<?php

namespace core\game\item\trinket;

use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\vanilla\Pickaxe;
use core\player\NexusPlayer;
use libs\utils\Utils;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

abstract class Trinket {

    const ABSORPTION_TRINKET = "Absorption Trinket";
    const GRAPPLING_TRINKET = "Grappling Trinket";
    const HEALING_TRINKET = "Healing Trinket";
    const RESISTANCE_TRINKET = "Resistance Trinket";

    /** @var string */
    private $name;

    /** @var string */
    private $effect;

    /** @var string */
    private $color;

    /** @var Item */
    private $display;

    /** @var int */
    private $cost;

    /** @var int */
    private $cooldown;

    /**
     * Trinket constructor.
     *
     * @param string $name
     * @param string $effect
     * @param string $color
     * @param Item $display
     * @param int $cost
     * @param int $cooldown
     */
    public function __construct(string $name, string $effect, string $color, Item $display, int $cost, int $cooldown) {
        $this->name = $name;
        $this->effect = $effect;
        $this->color = $color;
        $this->display = $display;
        $this->cost = $cost;
        $this->cooldown = $cooldown;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEffect(): string {
        return $this->effect;
    }

    /**
     * @return string
     */
    public function getColor(): string {
        return $this->color;
    }

    /**
     * @return Item
     */
    public function getDisplay(): Item {
        return $this->display;
    }

    /**
     * @return int
     */
    public function getCost(): int {
        return $this->cost;
    }

    /**
     * @return int
     */
    public function getCooldown(): int {
        return $this->cooldown;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return bool
     */
    abstract public function onItemUse(NexusPlayer $player): bool;
}
