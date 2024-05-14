<?php

namespace core\game\combat\guards\types;

use core\game\combat\guards\BaseGuard;
use core\Nexus;
use libs\utils\Utils;
use pocketmine\entity\Location;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class Safeguard extends BaseGuard {

    /**
     * Safeguard constructor.
     *
     * @param Location $location
     * @param CompoundTag|null $nbt
     */
    public function __construct(Location $location, ?CompoundTag $nbt = null) {
        $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR . "guard.png";
        parent::__construct($location, Utils::createSkin(Utils::getSkinDataFromPNG($path)), $nbt);
        $this->setMaxHealth(100);
        $this->setHealth(100);
        $hp = round($this->getHealth(), 1);
        $this->setNametag(TextFormat::BOLD . TextFormat::AQUA . "Guard");
        $this->setScoreTag(TextFormat::WHITE . $hp . TextFormat::BOLD . TextFormat::RED . " HP");
        $this->attackDamage = 2;
        $this->speed = 0.75;
        $this->defAttackWait = 20;
        $chest = ItemFactory::getInstance()->get(ItemIds::GOLDEN_CHESTPLATE);
        $this->getArmorInventory()->setChestplate($chest);
        $legs = ItemFactory::getInstance()->get(ItemIds::GOLDEN_LEGGINGS);
        $this->getArmorInventory()->setLeggings($legs);
        $boots = ItemFactory::getInstance()->get(ItemIds::GOLDEN_BOOTS);
        $this->getArmorInventory()->setBoots($boots);
        $sword = ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD);
        $this->getInventory()->setItemInHand($sword);
    }

    /**
     * @param int $tickDiff
     *
     * @return bool
     */
    protected function entityBaseTick(int $tickDiff = 1): bool {
        $hp = round($this->getHealth(), 1);
        $this->setScoreTag(TextFormat::WHITE . $hp . TextFormat::BOLD . TextFormat::RED . " HP");
        return parent::entityBaseTick($tickDiff);
    }
}