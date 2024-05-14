<?php

namespace core\game\item\sets\type;

use core\game\item\sets\Set;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Sword;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\color\Color;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;
use core\game\item\enchantment\types\armor\FinalStandEnchantment;

class YetiSet extends Set implements Listener {

    public function __construct(int $levelRequirement = 100)
    {
        parent::__construct($levelRequirement);
        Nexus::getInstance()->getServer()->getPluginManager()->registerEvents($this, Nexus::getInstance());
    }

    public function getName(): string
    {
        return "Yeti";
    }

    public function getColor(): string
    {
        return TextFormat::AQUA;
    }

    public function getHandItem(): Item
    {
        $axe = ItemFactory::getInstance()->get(ItemIds::DIAMOND_AXE);

        if($axe instanceof Axe) {
            $axe->setOriginalCustomName("§r§l§bYeti §7Axe");
            $axe->setOriginalLore([
                "",
                "§r§l§eSet Bonus: §bYeti",
                " §r§l§e*§r §b-7.5% Incoming Damage from Swords",
                " §r§l§e*§r §b25% chance to block Trap/Titan Trap",
                "§r§7(Requires all 4 Yeti armor pieces)",
            ]);
            $axe->getNamedTag()->setString(SetManager::SET, "yeti");
            $axe->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
        }

        return $axe;
    }

    public function getArmor(): array
    {
        $helmet = ItemFactory::getInstance()->get(ItemIds::LEATHER_HELMET);
        $chestplate = ItemFactory::getInstance()->get(ItemIds::LEATHER_CHESTPLATE);
        $leggings = ItemFactory::getInstance()->get(ItemIds::LEATHER_LEGGINGS);
        $boots = ItemFactory::getInstance()->get(ItemIds::LEATHER_BOOTS);

        $lore = [];
        $lore[] = "";
        $lore[] = "§r§l§eSet Bonus: §bYeti";
        $lore[] = " §r§l§e*§r §bPassive Thunder Blood";
        $lore[] = " §r§l§e*§r §bYeti's Wrath";
        $lore[] = " §r§l§e*§r §b+8 HP";
        $lore[] = "§r§7(Requires all 4 Yeti armor pieces)";

        if($helmet instanceof Armor && $chestplate instanceof Armor && $leggings instanceof Armor && $boots instanceof Armor) {
            $helmet->setOriginalCustomName("§r§l§bYeti §7Helmet");
            $chestplate->setOriginalCustomName("§r§l§bYeti §7Chestplate");
            $leggings->setOriginalCustomName("§r§l§bYeti §7Leggings");
            $boots->setOriginalCustomName("§r§l§bYeti §7Boots");
            $helmet->setOriginalLore($lore);
            $chestplate->setOriginalLore($lore);
            $leggings->setOriginalLore($lore);
            $boots->setOriginalLore($lore);
            $helmet->getNamedTag()->setString(SetManager::SET, "yeti");
            $chestplate->getNamedTag()->setString(SetManager::SET, "yeti");
            $leggings->getNamedTag()->setString(SetManager::SET, "yeti");
            $boots->getNamedTag()->setString(SetManager::SET, "yeti");
            $helmet->setCustomColor(new Color(0, 255, 255));
            $chestplate->setCustomColor(new Color(0, 255, 255));
            $leggings->setCustomColor(new Color(0, 255, 255));
            $boots->setCustomColor(new Color(0, 255, 255));
        }

        return [$helmet, $chestplate, $leggings, $boots];
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onDamage(EntityDamageByEntityEvent $event) : void
    {
        $damager = $event->getDamager();
        $victim = $event->getEntity();
        $multi = 1;

        if($victim instanceof NexusPlayer && $damager instanceof NexusPlayer && SetUtils::isWearingFullSet($victim, "yeti") && $victim->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "") === "yeti" && $damager->getInventory()->getItemInHand() instanceof Sword) {
            $multi -= 0.075;
        }

        if($damager instanceof NexusPlayer && SetUtils::isWearingFullSet($damager, "yeti")) {
            if(mt_rand(1, 100) <= 10) {
                if(!$victim instanceof NexusPlayer) {
                    $damager->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* YETI WRATH *");
                } else {
                    $damager->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* YETI WRATH [" . TextFormat::RESET . TextFormat::RED . $victim->getName() . TextFormat::BOLD . TextFormat::GRAY . "] *");
                }

                if($victim instanceof NexusPlayer && SetUtils::isWearingFullSet($victim, "underling") && isset(FinalStandEnchantment::$enhancedMovement[$victim->getUniqueId()->toString()])) {
                    $victim->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* [" . TextFormat::RESET . TextFormat::RED . "Blocked Yeti Wrath" . TextFormat::BOLD . TextFormat::GRAY . "] *");
                    return;
                }

                $multi += 0.1;
            }
        }

        $event->setBaseDamage($event->getBaseDamage() * $multi);
    }

    /**
     * @param EntityDamageEvent $event
     */
    public function onAllDamage(EntityDamageEvent $event) : void
    {
        $victim = $event->getEntity();
        $multi = 1;

        if($victim instanceof NexusPlayer && SetUtils::isWearingFullSet($victim, "yeti")) {
            $multi -= 0.05;
            // thunder blood ^
        }

        $event->setBaseDamage($event->getBaseDamage() * $multi);
    }
}