<?php

namespace core\game\item\prestige;

use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\vanilla\Pickaxe;
use libs\utils\Utils;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

abstract class Prestige {

    /** @var string */
    private $name;

    /** @var string */
    private $identifier;

    /** @var int|float */
    private $maximum;

    /** @var int|float */
    private $increment;

    /** @var int */
    private $prestigeRequirement;

    /**
     * Prestige constructor.
     *
     * @param string $name
     * @param string $identifier
     * @param int|float $maximum
     * @param int|float $increment
     * @param int $prestigeRequirement
     */
    public function __construct(string $name, string $identifier, $maximum, $increment, int $prestigeRequirement = 0) {
        $this->name = $name;
        $this->identifier = $identifier;
        $this->maximum = $maximum;
        $this->increment = $increment;
        $this->prestigeRequirement = $prestigeRequirement;
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return bool
     */
    public function isEligible(Pickaxe $pickaxe): bool {
        $current = $pickaxe->getAttribute($this->identifier);
        if($current >= $this->maximum or $pickaxe->getPrestige() < $this->prestigeRequirement) {
            return false;
        }
        return true;
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return int|float
     */
    public function getNewValue(Pickaxe $pickaxe) {
        $current = $pickaxe->getAttribute($this->identifier);
        $increment = $this->maximum - $current;
        if($increment > $this->increment) {
            if(is_float($this->increment)) {
                $increment = Utils::getRandomFloat($this->increment, $increment, strlen(explode(".", (string)$this->increment)[1]));
            }
            else {
                $increment = mt_rand($this->increment, $increment);
            }
        }
        return $current + $increment;
    }

    /**
     * @param Pickaxe $pickaxe
     *
     * @return Item
     */
    public function getDisplayItem(Pickaxe $pickaxe): Item {
        $item = $this->getDefaultDisplayItem();
        $current = $pickaxe->getAttribute($this->identifier);
        if($current >= $this->maximum or $pickaxe->getPrestige() < $this->prestigeRequirement) {
            $item = clone VanillaBlocks::BARRIER()->asItem();
        }
        $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . $this->getName());
        $prestige = $pickaxe->getPrestige();
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . $this->getDescription();
        if($current >= $this->maximum) {
            $lore[] = "";
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "This ability has been maxed out!";
        }
        else {
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Minimum increment: $this->increment)";
            if($this->prestigeRequirement > $prestige) {
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Ineligible for Prestige:";
                $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::RED . "Prestige Level" . TextFormat::GRAY . " - " . TextFormat::WHITE . EnchantmentManager::getRomanNumber($prestige) . TextFormat::GRAY . " / " . TextFormat::WHITE . EnchantmentManager::getRomanNumber($this->prestigeRequirement);
            }
            else {
                if($this->prestigeRequirement > 0) {
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Requirement Met:";
                    $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::GREEN . "Prestige Level" . TextFormat::GRAY . " - " . TextFormat::WHITE . EnchantmentManager::getRomanNumber($prestige) . TextFormat::GRAY . " / " . TextFormat::WHITE . EnchantmentManager::getRomanNumber($this->prestigeRequirement);
                }
                $lore[] = "";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . " >> Click to select <<";
            }
        }
        return $item->setLore($lore);
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
    public function getIdentifier(): string {
        return $this->identifier;
    }

    /**
     * @return float|int
     */
    public function getMaximum() {
        return $this->maximum;
    }

    /**
     * @return float|int
     */
    public function getIncrement() {
        return $this->increment;
    }

    /**
     * @return int
     */
    public function getPrestigeRequirement(): int {
        return $this->prestigeRequirement;
    }

    /**
     * @return Item
     */
    abstract public function getDefaultDisplayItem(): Item;

    /**
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * @param Pickaxe $pickaxe
     *
     * @return string
     */
    abstract public function getLore(Pickaxe $pickaxe): string;
}
