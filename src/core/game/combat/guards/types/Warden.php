<?php

namespace core\game\combat\guards\types;

use core\game\combat\guards\BaseGuard;
use core\Nexus;
use libs\utils\Utils;
use pocketmine\entity\Location;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class Warden extends BaseGuard {

    const ATTACK_DISTANCE = 20;

    /**
     * Safeguard constructor.
     *
     * @param Location $location
     * @param CompoundTag|null $nbt
     */
    public function __construct(Location $location, ?CompoundTag $nbt = null) {
        $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR . "warden.png";
        parent::__construct($location, Utils::createSkin(Utils::getSkinDataFromPNG($path)), $nbt);
        $this->setMaxHealth(1000);
        $this->setHealth(1000);
        $hp = round($this->getHealth(), 1);
        $this->setNametag(TextFormat::BOLD . TextFormat::RED . "Warden");
        $this->setScoreTag(TextFormat::WHITE . $hp . TextFormat::BOLD . TextFormat::RED . " HP");
        $this->attackDamage = 16;
        $this->speed = 1.5;
        $this->defAttackWait = 10;
        $ench = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1);
        $chest = ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE);
        $chest->addEnchantment($ench);
        $this->getArmorInventory()->setChestplate($chest);
        $legs = ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS);
        $legs->addEnchantment($ench);
        $this->getArmorInventory()->setLeggings($legs);
        $boots = ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS);
        $boots->addEnchantment($ench);
        $this->getArmorInventory()->setBoots($boots);
        $axe = ItemFactory::getInstance()->get(ItemIds::DIAMOND_AXE);
        $axe->addEnchantment($ench);
        $this->getInventory()->setItemInHand($axe);
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