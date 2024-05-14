<?php

declare(strict_types = 1);

namespace core\game\item\sets\type;

use core\game\item\enchantment\Enchantment;
use core\game\item\sets\Set;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\Nexus;
use core\player\gang\GangManager;
use core\player\NexusPlayer;
use pocketmine\color\Color;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class LunacySet extends Set implements Listener
{
    /**
     * @param int $levelRequirement
     */
    public function __construct(int $levelRequirement = 100)
    {
        parent::__construct($levelRequirement);
        Nexus::getInstance()->getServer()->getPluginManager()->registerEvents($this, Nexus::getInstance());
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return "Lunacy";
    }

    /**
     * @return string
     */
    public function getColor() : string
    {
        return TextFormat::GRAY;
    }

    /**
     * @return Item
     */
    public function getHandItem(): Item
    {
        $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_AXE);

        if($item instanceof Axe) {
            $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Lunacy " . TextFormat::GRAY . "Axe");
            $item->setOriginalLore([
                "",
                TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Set Bonus: " . TextFormat::DARK_GREEN . "Lunacy",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_PURPLE . "+3% Chance to deal double damage.",
                "",
                TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Lunacy armor pieces)",
            ]);
            $item->getNamedTag()->setString(SetManager::SET, "lunacy");
        }

        $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));

        return $item;
    }

    /**
     * @return Item[]
     */
    public function getArmor(): array
    {
        $head = ItemFactory::getInstance()->get(ItemIds::LEATHER_CAP);
        $chest = ItemFactory::getInstance()->get(ItemIds::LEATHER_TUNIC);
        $leggings = ItemFactory::getInstance()->get(ItemIds::LEATHER_PANTS);
        $boots = ItemFactory::getInstance()->get(ItemIds::LEATHER_BOOTS);
        $armorLore = [
            "",
            TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Set Bonus: " . TextFormat::DARK_PURPLE . "Lunacy",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_PURPLE . "+10% Outgoing Damage",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_PURPLE . "+10% Outgoing Damage for Energy Enchants",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_PURPLE . "Deathbringer",
            TextFormat::RESET . TextFormat::GRAY . "(Chance to cause double damage)",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_PURPLE . "Molten Armor",
            TextFormat::RESET . TextFormat::GRAY . "(Chance on incoming damage to set nearby",
            TextFormat::RESET . TextFormat::GRAY . "enemies on fire) (Radius: 4 blocks)",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_PURPLE . "Fire Pit",
            TextFormat::RESET.  TextFormat::GRAY . "(Chance to deal AoE fire damage when",
            TextFormat::RESET . TextFormat::GRAY . "target enemy is on fire) (Radius: 5 blocks)",
            "",
            TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Lunacy armor pieces)",
            "",
            TextFormat::RESET . TextFormat::YELLOW . "This Armor is as strong as Diamond",
        ];

        if($head instanceof Armor && $chest instanceof Armor && $leggings instanceof Armor && $boots instanceof Armor) {
            $head->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Lunacy" . TextFormat::GRAY . "Helmet");
            $head->setOriginalLore($armorLore);
            $head->setCustomColor(new Color(170, 170, 170));
            $head->getNamedTag()->setString(SetManager::SET, "lunacy");
            $chest->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Lunacy " . TextFormat::GRAY . "Chestplate");
            $chest->setOriginalLore($armorLore);
            $chest->setCustomColor(new Color(170, 170, 170));
            $chest->getNamedTag()->setString(SetManager::SET, "lunacy");
            $leggings->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Lunacy " . TextFormat::GRAY . "Leggings");
            $leggings->setOriginalLore($armorLore);
            $leggings->setCustomColor(new Color(170, 170, 170));
            $leggings->getNamedTag()->setString(SetManager::SET, "lunacy");
            $boots->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Lunacy " . TextFormat::GRAY .  "Boots");
            $boots->setOriginalLore($armorLore);
            $boots->setCustomColor(new Color(170, 170, 170));
            $boots->getNamedTag()->setString(SetManager::SET, "lunacy");
        }

        return [$head, $chest, $leggings, $boots];
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onDamage(EntityDamageByEntityEvent $event) : void
    {
        $damager = $event->getDamager();
        $victim = $event->getEntity();
        $multi = 1;

        if($damager instanceof NexusPlayer && SetUtils::isWearingFullSet($damager, "lunacy")) {
            $multi += 0.1;


            foreach ($damager->getArmorInventory()->getContents() as $ae) {
                if($ae instanceof Armor) {
                    foreach ($ae->getEnchantments() as $enchantment) {
                        $type = $enchantment->getType();
                        if($type instanceof Enchantment && $type->getRarity() === Enchantment::ENERGY) $multi += 0.1;
                    }
                }
            }
        }

        if($damager instanceof NexusPlayer && SetUtils::isWearingFullSet($damager, "lunacy") && $damager->getInventory()->getItemInHand()->getNamedTag()->getString("set", "") === "lunacy") {
            if(mt_rand(0, 100) <= 2) $multi++;
        }

        if($victim instanceof NexusPlayer && SetUtils::isWearingFullSet($victim, "lunacy")) {
            foreach ($victim->getWorld()->getNearbyEntities($victim->getBoundingBox()->expandedCopy(4, 4, 4)) as $e) {
                if($e instanceof NexusPlayer && $e->getDataSession()->getGang() !== null && $victim->getDataSession()->getGang() !== null && $e->getDataSession()->getGang()->isInGang($victim->getName())) continue;
                if(mt_rand(3, 100) <= 4) $e->setOnFire(3);
            }

            foreach ($victim->getWorld()->getNearbyEntities($victim->getBoundingBox()->expandedCopy(5, 5, 5)) as $eTwo) {
                if($eTwo instanceof NexusPlayer && $eTwo->getDataSession()->getGang() !== null && $victim->getDataSession()->getGang() !== null && $eTwo->getDataSession()->getGang()->isInGang($victim->getName())) continue;
                if($eTwo->isOnFire()) $multi += 0.03;
            }
        }

        $event->setBaseDamage($event->getBaseDamage() * $multi);
    }
}