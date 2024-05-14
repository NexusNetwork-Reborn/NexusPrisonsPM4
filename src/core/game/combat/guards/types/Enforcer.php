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

class Enforcer extends BaseGuard {

    public function __construct(Location $location, ?CompoundTag $nbt = null) {
        $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR. "enforcer.png";
        parent::__construct($location, Utils::createSkin(Utils::getSkinDataFromPNG($path)), $nbt);
        $this->setMaxHealth(250);
        $this->setHealth(250);
        $hp = round($this->getHealth(), 1);
        $this->setNametag(TextFormat::BOLD . TextFormat::YELLOW . "Enforcer");
        $this->setScoreTag(TextFormat::WHITE . $hp . TextFormat::BOLD . TextFormat::RED . " HP");
        $this->attackDamage = 8;
        $this->speed = 1;
        $this->defAttackWait = 20;
        $ench = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1);
        $chest = ItemFactory::getInstance()->get(ItemIds::GOLDEN_CHESTPLATE);
        $chest->addEnchantment($ench);
        $this->getArmorInventory()->setChestplate($chest);
        $legs = ItemFactory::getInstance()->get(ItemIds::GOLDEN_LEGGINGS);
        $legs->addEnchantment($ench);
        $this->getArmorInventory()->setLeggings($legs);
        $boots = ItemFactory::getInstance()->get(ItemIds::GOLDEN_BOOTS);
        $boots->addEnchantment($ench);
        $this->getArmorInventory()->setBoots($boots);
        $sword = ItemFactory::getInstance()->get(ItemIds::IRON_SWORD);
        $sword->addEnchantment($ench);
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