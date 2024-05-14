<?php

namespace core\game\item\sets\type;

use core\game\item\sets\Set;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Sword;
use core\Nexus;
use core\player\NexusPlayer;
use Grpc\Call;
use pocketmine\color\Color;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class GhostSet extends Set implements Listener {

    public function __construct(int $levelRequirement = 100)
    {
        parent::__construct($levelRequirement);
        Nexus::getInstance()->getServer()->getPluginManager()->registerEvents($this, Nexus::getInstance());
    }

    public function getName(): string
    {
        return "Ghost";
    }

    public function getColor(): string
    {
        return TextFormat::DARK_PURPLE;
    }

    /**
     * @return Item
     */
    public function getHandItem(): Item
    {
        $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD);

        if($item instanceof Sword) {
            $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Ghost " . TextFormat::GRAY . "Sword");
            $item->setOriginalLore([
                "",
                TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Set Bonus: " . TextFormat::DARK_PURPLE . "Ghost",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_PURPLE . "Double Strike (2% chance to deal double damage)",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_PURPLE . "Immunity to Slowness",
                "",
                TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Ghost armor pieces)",
            ]);
            $item->getNamedTag()->setString(SetManager::SET, "ghost");

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
            TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Set Bonus: " . TextFormat::DARK_PURPLE . "Ghost",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_PURPLE . "Gears IV",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_PURPLE . "Hidden from /near",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_PURPLE . "Passive 10% Dodge",
            "",
            TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Ghost armor pieces)",
            "",
            TextFormat::RESET . TextFormat::YELLOW . "This Armor is as strong as Diamond",
        ];

        if($head instanceof Armor && $chest instanceof Armor && $leggings instanceof Armor && $boots instanceof Armor) {
            $head->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Ghost " . TextFormat::GRAY . "Helmet");
            $head->setOriginalLore($armorLore);
            $head->setCustomColor(new Color(60, 31, 64));
            $head->getNamedTag()->setString(SetManager::SET, "ghost");
            $chest->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Ghost " . TextFormat::GRAY . "Chestplate");
            $chest->setOriginalLore($armorLore);
            $chest->setCustomColor(new Color(60, 31, 64));
            $chest->getNamedTag()->setString(SetManager::SET, "ghost");
            $leggings->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Ghost " . TextFormat::GRAY . "Leggings");
            $leggings->setOriginalLore($armorLore);
            $leggings->setCustomColor(new Color(60, 31, 64));
            $leggings->getNamedTag()->setString(SetManager::SET, "ghost");
            $boots->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Ghost " . TextFormat::GRAY .  "Boots");
            $boots->setOriginalLore($armorLore);
            $boots->setCustomColor(new Color(60, 31, 64));
            $boots->getNamedTag()->setString(SetManager::SET, "ghost");
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

        if($damager instanceof NexusPlayer && mt_rand(1, 100) <= 2 && SetUtils::isWearingFullSet($damager, "ghost") && $damager->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "") === "ghost") $multi++;

        $event->setBaseDamage($event->getBaseDamage() * $multi);

        if($victim instanceof NexusPlayer && mt_rand(1, 100) <= 10 && SetUtils::isWearingFullSet($victim, "ghost")) {
            $event->cancel();
            $victim->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* DODGE [" . TextFormat::RESET . TextFormat::RED . $victim->getName() . TextFormat::GRAY . TextFormat::BOLD . "] *");
            if($damager instanceof NexusPlayer) $damager->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* MANEUVER [" . TextFormat::RESET . TextFormat::RED . $victim->getName() . TextFormat::GRAY . TextFormat::BOLD . "] *");
        }
    }

    /**
     * @param EntityEffectAddEvent $event
     */
    public function onEffect(EntityEffectAddEvent $event) : void
    {
        $player = $event->getEntity();

        if($player instanceof NexusPlayer && SetUtils::isWearingFullSet($player, "ghost") && $player->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "") === "ghost" && $event->getEffect()->getType()->getName() === VanillaEffects::SLOWNESS()->getName()) $event->cancel();
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event) : void
    {
        if(SetUtils::isWearingFullSet($event->getPlayer(), "ghost")) $event->getPlayer()->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 2147483647, 3));

        $event->getPlayer()->getArmorInventory()->getListeners()->add(new CallbackInventoryListener(function (Inventory $inventory, int $slot, Item $oldItem) use($event) : void {
            if(SetUtils::isWearingFullSet($event->getPlayer(), "ghost")) {
                $event->getPlayer()->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 2147483647, 3));
            } else {
                if($oldItem->getNamedTag()->getString(SetManager::SET, "") === "ghost" && $event->getPlayer()->getEffects()->has(VanillaEffects::SPEED())) $event->getPlayer()->getEffects()->remove(VanillaEffects::SPEED());
            }
        }, function (Inventory $inventory, $oldItems) use($event) : void {
            if(SetUtils::isWearingFullSet($event->getPlayer(), "ghost")) {
                $event->getPlayer()->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 2147483647, 3));
            } else {
                foreach($oldItems as $oldItem) {
                    if($oldItem->getNamedTag()->getString(SetManager::SET, "") === "ghost" && $event->getPlayer()->getEffects()->has(VanillaEffects::SPEED())) $event->getPlayer()->getEffects()->remove(VanillaEffects::SPEED());
                }
            }
        }));
    }
}