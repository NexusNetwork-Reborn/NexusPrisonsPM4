<?php

declare(strict_types = 1);

namespace core\game\item\sets\type;

use core\game\item\sets\Set;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\Nexus;
use pocketmine\color\Color;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use core\player\NexusPlayer;
use core\game\item\enchantment\types\armor\FinalStandEnchantment;

class UnderlingSet extends Set implements Listener
{
    /** @var array */
    private static array $brokenArmors = [];

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
        return "Underling";
    }

    /**
     * @return string
     */
    public function getColor() : string
    {
        return TextFormat::GREEN;
    }

    /**
     * @return Item
     */
    public function getHandItem(): Item
    {
        $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_AXE);

        if($item instanceof Axe) {
            $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Underling " . TextFormat::GRAY . "Axe");
            $item->setOriginalLore([
                "",
                TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Set Bonus: " . TextFormat::GREEN . "Underling",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GREEN . "Chance to deal mass durability damage",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GREEN . "Chance to instantly break enemy gear when it is below 20% durability",
                "",
                TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Underling armor pieces)",
            ]);
            $item->getNamedTag()->setString(SetManager::SET, "underling");
            $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
        }

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
            TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Set Bonus: " . TextFormat::GREEN . "Underling",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GREEN . "-15% Incoming Damage",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GREEN . "+5% Outgoing Damage",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GREEN . "Plague Immunity",
            TextFormat::RESET . TextFormat::GRAY . "(Chance on incoming damage to gain immunity",
            TextFormat::RESET . TextFormat::GRAY . "to ignore Plagued Smite's healing debuff.)",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GREEN . "Furious Reboot",
            TextFormat::RESET . TextFormat::GRAY . "(Upon System Reboot activates for 45 seconds:",
            TextFormat::RESET . TextFormat::GRAY . "- Immunity to Yeti Wrath",
            TextFormat::RESET . TextFormat::GRAY . "- Chance to cause double damage",
            TextFormat::RESET . TextFormat::GRAY . "- +10% Movement Speed)",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GREEN . "Underdog's Adventure",
            TextFormat::RESET.  TextFormat::GRAY . "(Upon breaking an enemies piece of gear,",
            TextFormat::RESET . TextFormat::GRAY . "gain +10% Outgoing Damage for 30 seconds)",
            "",
            TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Underling armor pieces)",
            "",
            TextFormat::RESET . TextFormat::YELLOW . "This Armor is as strong as Diamond",
        ];

        if($head instanceof Armor && $chest instanceof Armor && $leggings instanceof Armor && $boots instanceof Armor) {
            $head->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Underling" . TextFormat::GRAY . "Helmet");
            $head->setOriginalLore($armorLore);
            $head->setCustomColor(new Color(0, 255, 0));
            $head->getNamedTag()->setString(SetManager::SET, "underling");
            $chest->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Underling " . TextFormat::GRAY . "Chestplate");
            $chest->setOriginalLore($armorLore);
            $chest->setCustomColor(new Color(0, 255, 0));
            $chest->getNamedTag()->setString(SetManager::SET, "underling");
            $leggings->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Underling " . TextFormat::GRAY . "Leggings");
            $leggings->setOriginalLore($armorLore);
            $leggings->setCustomColor(new Color(0, 255, 0));
            $leggings->getNamedTag()->setString(SetManager::SET, "underling");
            $boots->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Underling " . TextFormat::GRAY .  "Boots");
            $boots->setOriginalLore($armorLore);
            $boots->setCustomColor(new Color(0, 255, 0));
            $boots->getNamedTag()->setString(SetManager::SET, "underling");
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

        if($damager instanceof NexusPlayer && $victim instanceof NexusPlayer && $damager->getInventory()->getItemInHand()->getNamedTag()->getString("set", "") === "underling" && SetUtils::isWearingFullSet($damager, "underling")) {
            if(mt_rand(0, 250) <= 5) {
                $victim->damageArmor(50.0);
            }

            $broken = false;
            $id = $damager->getUniqueId()->toString();

            foreach($victim->getArmorInventory()->getContents() as $armor) {
                if($armor instanceof Armor) {
                    $maxDurability = $armor->getMaxDurability();
                    if($armor->getDamage() + $event->getFinalDamage() >= (int)floor($maxDurability * 0.8) && mt_rand(0, 250) <= 2) {
                        $armor->setDamage($armor->getMaxDurability());
                        $broken = true;
                    }
                }
            }

            if($broken) {
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use($id) : void {
                    if(isset(self::$brokenArmors[$id])) unset(self::$brokenArmors[$id]);
                }), 30 * 20);
            }
        }

        $multi = 1;

        if($damager instanceof NexusPlayer && SetUtils::isWearingFullSet($damager, "underling")) {
            $multi += 0.05;

            foreach($victim->getArmorInventory()->getContents() as $a) {
                if($a instanceof Armor && ($a->getDamage() + $event->getFinalDamage()) >= $a->getMaxDurability()) {
                    Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use($id) : void {
                        if(isset(self::$brokenArmors[$id])) unset(self::$brokenArmors[$id]);
                    }), 30 * 20);
                }
            }

            if(isset(self::$brokenArmors[$damager->getUniqueId()->toString()])) $multi += 0.1;

            if((time() - $damager->getCESession()->getLastSystemReboot()) <= 45 && mt_rand(1, 100) <= 5) {
                $multi += 2;
            }
        }

        if($victim instanceof NexusPlayer && SetUtils::isWearingFullSet($victim, "underling")) {
            $multi -= 0.15;
        }

        $event->setBaseDamage($event->getBaseDamage() * $multi);
    }
}