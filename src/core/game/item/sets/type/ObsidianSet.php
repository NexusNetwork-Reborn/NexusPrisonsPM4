<?php

namespace core\game\item\sets\type;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentIdentifiers;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\sets\Set;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\color\Color;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class ObsidianSet extends Set implements Listener {

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
    public function getName(): string
    {
        return "Obsidian";
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return TextFormat::DARK_GRAY;
    }

    /**
     * @return Item
     */
    public function getHandItem(): Item
    {
        $axe = ItemFactory::getInstance()->get(ItemIds::DIAMOND_AXE);

        if($axe instanceof Axe) {
            $axe->setOriginalCustomName("§r§l§0Obsidian §7Axe§r");
            $axe->setOriginalLore([
                "",
                "§r§l§eSet Bonus: §0Obsidian",
                " §r§e§l*§r §8+10% Outgoing Durability Damage",
                " §r§e§l*§r §8Scorch IV",
                "§r§7(Requires all 4 Obsidian armor pieces)",
            ]);
            $axe->getNamedTag()->setString(SetManager::SET, "obsidian");
        }

        $axe->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));

        return $axe;
    }

    /**
     * @return Item[]
     */
    public function getArmor(): array
    {
        $helmet = ItemFactory::getInstance()->get(ItemIds::LEATHER_HELMET);
        $chestplate = ItemFactory::getInstance()->get(ItemIds::LEATHER_CHESTPLATE);
        $leggings = ItemFactory::getInstance()->get(ItemIds::LEATHER_LEGGINGS);
        $boots = ItemFactory::getInstance()->get(ItemIds::LEATHER_BOOTS);

        $lore = [];
        $lore[] = "";
        $lore[] = "§r§l§eSet Bonus: §0Obsidian";
        $lore[] = " §r§l§e*§r §0Passive Fire Resistance";
        $lore[] = " §r§l§e*§r §0+10% Outgoing Damage";
        $lore[] = " §r§l§e*§r §0-10% Incoming Damage";
        $lore[] = " §r§l§e*§r §0+25% Durability";
        $lore[] = " §r§l§e*§r §0+15% More XP from Clue Caskets";
        $lore[] = "§r§7(Requires all 4 Obsidian armor Pieces)";

        if($helmet instanceof Armor && $chestplate instanceof Armor && $leggings instanceof Armor && $boots instanceof Armor) {
            $helmet->setOriginalCustomName("§r§l§0Obsidian §7Helmet");
            $chestplate->setOriginalCustomName("§r§l§0Obsidian §7Chestplate");
            $leggings->setOriginalCustomName("§r§l§0Obsidian §7Leggings");
            $boots->setOriginalCustomName("§r§l§0Obsidian §7Boots");
            $helmet->setOriginalLore($lore);
            $chestplate->setOriginalLore($lore);
            $leggings->setOriginalLore($lore);
            $boots->setOriginalLore($lore);
            $helmet->getNamedTag()->setString(SetManager::SET, "obsidian");
            $chestplate->getNamedTag()->setString(SetManager::SET, "obsidian");
            $leggings->getNamedTag()->setString(SetManager::SET, "obsidian");
            $boots->getNamedTag()->setString(SetManager::SET, "obsidian");
            $helmet->setCustomColor(new Color(83, 89, 93));
            $chestplate->setCustomColor(new Color(83, 89, 93));
            $leggings->setCustomColor(new Color(83, 89, 93));
            $boots->setCustomColor(new Color(83, 89, 93));
        }

        return [$helmet, $chestplate, $leggings, $boots];
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event) : void
    {
        if(SetUtils::isWearingFullSet($event->getPlayer(), "obsidian")) $event->getPlayer()->getEffects()->add(new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), 2147483647, 1));

        $event->getPlayer()->getArmorInventory()->getListeners()->add(new CallbackInventoryListener(function (Inventory $inventory, int $slot, Item $oldItem) use($event) : void {
            if(SetUtils::isWearingFullSet($event->getPlayer(), "obsidian")) {
                $event->getPlayer()->getEffects()->add(new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), 2147483647, 3));
            } else {
                if($oldItem->getNamedTag()->getString(SetManager::SET, "") === "obsidian" && $event->getPlayer()->getEffects()->has(VanillaEffects::FIRE_RESISTANCE())) $event->getPlayer()->getEffects()->remove(VanillaEffects::FIRE_RESISTANCE());
            }
        }, function (Inventory $inventory, $oldItems) use($event) : void {
            if(SetUtils::isWearingFullSet($event->getPlayer(), "obsidian")) {
                $event->getPlayer()->getEffects()->add(new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), 2147483647, 3));
            } else {
                foreach($oldItems as $oldItem) {
                    if($oldItem->getNamedTag()->getString(SetManager::SET, "") === "obsidian" && $event->getPlayer()->getEffects()->has(VanillaEffects::FIRE_RESISTANCE())) $event->getPlayer()->getEffects()->remove(VanillaEffects::FIRE_RESISTANCE());
                }
            }
        }));
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onDamage(EntityDamageByEntityEvent $event) : void
    {
        $damager = $event->getDamager();
        $victim = $event->getEntity();
        $multi = 1;

        if($damager instanceof NexusPlayer && SetUtils::isWearingFullSet($damager, "obsidian")) {
            $multi += 0.1;
        }

        if($victim instanceof NexusPlayer && SetUtils::isWearingFullSet($victim, "obsidian")) {
            $multi -= 0.1;

            foreach ($victim->getArmorInventory()->getContents() as $c) {
                if($c instanceof Armor) {
                    $newDmg = $c->getDamage() - (int)ceil($event->getFinalDamage() * 0.75);
                    if($newDmg < 0) {
                        $newDmg = 0;
                    }
                    // 25% increase durability -> take less armor damage essentially.
                    $c->setDamage($newDmg);
                }
            }
        }

        if($damager instanceof NexusPlayer && $victim instanceof NexusPlayer && SetUtils::isWearingFullSet($damager, "obsidian") && $damager->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "") === "obsidian") {
            foreach ($damager->getArmorInventory()->getContents() as $armor) {
                if($armor instanceof Armor) $armor->applyDamage((int)ceil($event->getFinalDamage() * 1.1));
            }

            $random = mt_rand(1, 175);
            $chance = 4 * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $scorch = $victim;
                $deflect = $victim->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(EnchantmentIdentifiers::DEFLECT));
                if($deflect > 0) {
                    if($deflect >= mt_rand(1, 24)) {
                        $scorch = $damager;
                    }
                }
                $scorch->setOnFire(16);
            }
        }

        $event->setBaseDamage($event->getBaseDamage() * $multi);
    }
}