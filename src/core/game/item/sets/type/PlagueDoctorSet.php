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
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class PlagueDoctorSet extends Set implements Listener {

    /** @var array */
    public static array $reduceHealing = [];

    public function __construct(int $levelRequirement = 100)
    {
        parent::__construct($levelRequirement);
        Nexus::getInstance()->getServer()->getPluginManager()->registerEvents($this, Nexus::getInstance());
    }

    public function getName(): string
    {
        return "PlagueDoctor";
    }

    public function getColor(): string
    {
        return TextFormat::DARK_GREEN;
    }

    /**
     * @return Item
     */
    public function getHandItem(): Item
    {
        $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD);

        if($item instanceof Sword) {
            $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_PURPLE . "Plague Doctor " . TextFormat::GRAY . "Sword");
            $item->setOriginalLore([
                "",
                TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Set Bonus: " . TextFormat::DARK_GREEN . "Plague Doctor",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_GREEN . "-7.5% Incoming Damage from Axe users",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_GREEN . "Successive attacks add 1.5% chance to bypass enemy defensive enchantments " . TextFormat::GRAY . "(Max: 25 Stacks)",
                " " . TextFormat::RESET . TextFormat::GRAY . "(When hit by an enemy lose 30% of your current stacks)",
                "",
                TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Plague Doctor armor pieces)",
            ]);
            $item->getNamedTag()->setString(SetManager::SET, "plaguedoctor");
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
            TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Set Bonus: " . TextFormat::DARK_GREEN . "Plague Doctor",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_GREEN . "+5 HP",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_GREEN . "Titan Slayer",
            TextFormat::RESET . TextFormat::GRAY . "(50% chance to bypass titan Blood Enchant)",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_GREEN . "Viral Corruption",
            TextFormat::RESET . TextFormat::GRAY . "(12.5% chance to debuff enemies with 50% Reduced Healing",
            TextFormat::RESET . TextFormat::GRAY . "for 5 seconds in a 5 block radius)",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::DARK_GREEN . "Shattering Blow",
            TextFormat::RESET.  TextFormat::GRAY . "(+0.3% Increased Damage for every 1% missing",
            TextFormat::RESET . TextFormat::GRAY . "in the enemy's lowest durability armor piece)",
            "",
            TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Plague Doctor armor pieces)",
            "",
            TextFormat::RESET . TextFormat::YELLOW . "This Armor is as strong as Diamond",
        ];

        if($head instanceof Armor && $chest instanceof Armor && $leggings instanceof Armor && $boots instanceof Armor) {
            $head->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_GREEN . "Plague Doctor" . TextFormat::GRAY . "Helmet");
            $head->setOriginalLore($armorLore);
            $head->setCustomColor(new Color(0, 100, 0));
            $head->getNamedTag()->setString(SetManager::SET, "plaguedoctor");
            $chest->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_GREEN . "Plague Doctor " . TextFormat::GRAY . "Chestplate");
            $chest->setOriginalLore($armorLore);
            $chest->setCustomColor(new Color(0, 100, 0));
            $chest->getNamedTag()->setString(SetManager::SET, "plaguedoctor");
            $leggings->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_GREEN . "Plague Doctor " . TextFormat::GRAY . "Leggings");
            $leggings->setOriginalLore($armorLore);
            $leggings->setCustomColor(new Color(0, 100, 0));
            $leggings->getNamedTag()->setString(SetManager::SET, "plaguedoctor");
            $boots->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_GREEN . "Plague Doctor " . TextFormat::GRAY .  "Boots");
            $boots->setOriginalLore($armorLore);
            $boots->setCustomColor(new Color(0, 100, 0));
            $boots->getNamedTag()->setString(SetManager::SET, "plaguedoctor");
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

        if($victim instanceof NexusPlayer && $damager instanceof NexusPlayer && SetUtils::isWearingFullSet($victim, "plaguedoctor") && $victim->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "") === "plaguedoctor" && $damager->getInventory()->getItemInHand() instanceof Axe) {
            $multi -= 0.075;
        }

        if($damager instanceof NexusPlayer && $victim instanceof NexusPlayer && SetUtils::isWearingFullSet($damager, "plaguedoctor")) {
            $durability = [];

            foreach($victim->getArmorInventory()->getContents() as $content) {
                if($content instanceof Armor) $durability[$content->getMaxDurability()] = $content->getDamage();
            }

            if(!empty($durability)) {
                arsort($durability, SORT_DESC);

                $least = $durability[array_key_first($durability)];
                $maxDurability = array_key_first($durability);
                $onePercent = (int)floor($maxDurability / 100);
                $multiForDeci = (int)floor($least / $onePercent);

                $multi += (0.03 * $multiForDeci);
            }
        }

        if($victim instanceof NexusPlayer && SetUtils::isWearingFullSet($victim, "plaguedoctor")) {
            if(mt_rand(1, 1000) <= 125) {
                $victim->addCEPopup(TextFormat::GRAY . TextFormat::BOLD . "* VIRAL CORRUPTION *");
                foreach ($victim->getWorld()->getNearbyEntities($victim->getBoundingBox()->expandedCopy(5.0, 5.0, 5.0)) as $entity) {
                    if(!$entity instanceof NexusPlayer || $entity === $victim) continue;
                    if(isset(self::$reduceHealing[$entity->getUniqueId()->toString()])) continue;
                    if($entity->getDataSession()->getGang() !== null && $victim->getDataSession()->getGang() !== null && $victim->getDataSession()->getGang()->isInGang($entity->getName())) continue;

                    $id = $entity->getUniqueId()->toString();
                    self::$reduceHealing[$id] = $id;

                    $entity->addCEPopup(TextFormat::GRAY . "* VIRAL CORRUPTION [" . TextFormat::RESET . TextFormat::RED . $victim->getName() . TextFormat::BOLD . TextFormat::GRAY . "] *");

                    Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($id) {
                        if(isset(self::$reduceHealing[$id])) unset(self::$reduceHealing[$id]);
                    }), 20 * 5);
                }
            }
        }

        if($damager instanceof NexusPlayer && SetUtils::isWearingFullSet($damager, "plaguedoctor") && $damager->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "") === "plaguedoctor") {
            $damager->getCESession()->addBypassHit();

            $hits = $damager->getCESession()->getBypassHits();
            $cap = 37.5;
            $percent = round($hits * 0.015);
            $percent = $percent < $cap ? $percent : $cap;

            if($cap !== $percent) {
                $showPercentage = $percent * 100;
                $damager->addCEPopup(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "** PLAGUE DOCTOR [" . TextFormat::RESET . TextFormat::GRAY . "+$showPercentage%%%" . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "] **");
            }
        }

        if($victim instanceof NexusPlayer && SetUtils::isWearingFullSet($victim, "plaguedoctor")) {
            if($victim->getCESession()->getBypassHits() > 0) {
                if($victim->getCESession()->getBypassHits() >= 3) {
                    $victim->getCESession()->setBypassHits((int)floor($victim->getCESession()->getBypassHits() * 0.7));
                } else {
                    $victim->getCESession()->resetBypassHits();
                }
            }
        }

        $event->setBaseDamage($event->getBaseDamage() * $multi);
    }

    /**
     * @param EntityRegainHealthEvent $event
     */
    public function onHeal(EntityRegainHealthEvent $event) : void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof NexusPlayer) {
            return;
        }
        if (!SetUtils::isWearingFullSet($entity, "plaguedoctor")) {
            return;
        }
        if(!isset(self::$reduceHealing[$entity->getUniqueId()->toString()])) {
            return;
        }
        $event->setAmount($event->getAmount() * 0.5);
    }
}