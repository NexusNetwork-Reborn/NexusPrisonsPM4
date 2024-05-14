<?php

declare(strict_types = 1);

namespace core\game\item\sets\type;

use core\game\item\event\EarnEnergyEvent;
use core\game\item\sets\Set;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Pickaxe;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\BlockLegacyIds;
use pocketmine\color\Color;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class DemolitionSet extends Set implements Listener
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
    public function getName(): string
    {
        return "Demolition";
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return TextFormat::DARK_RED;
    }

    /**
     * @return Item
     *
     * For this one I'm using ->getColor as I can't
     * tell if it's dark red or red lol.
     */
    public function getHandItem(): Item
    {
        $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE);

        if($item instanceof Pickaxe) {
            $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->getColor() . "Demolition " . TextFormat::GRAY . "Pickaxe");
            $item->setOriginalLore([
                "",
                TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Set Bonus: " . $this->getColor() . "Demolition",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . $this->getColor() . "1.1x Energy Collector & Energy Hoarder effect",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . $this->getColor() . "+10% XP Gain",
                "",
                TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Demolition armor pieces)",
            ]);
            $item->getNamedTag()->setString(SetManager::SET, "demolition");
        }

        $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));

        return $item;
    }

    public function getArmor(): array
    {
        $head = ItemFactory::getInstance()->get(ItemIds::LEATHER_CAP);
        $chest = ItemFactory::getInstance()->get(ItemIds::LEATHER_TUNIC);
        $leggings = ItemFactory::getInstance()->get(ItemIds::LEATHER_PANTS);
        $boots = ItemFactory::getInstance()->get(ItemIds::LEATHER_BOOTS);
        $armorLore = [
            "",
            TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Set Bonus: " . $this->getColor() . "Demolition",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . $this->getColor() . "+10% XP Gain",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . $this->getColor() . "+50% Energy Gain",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . $this->getColor() . "+50% Bonus Efficiency",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . $this->getColor() . "+25% Ore Gain in Cells",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . $this->getColor() . "+8 HP",
            "",
            TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Demolition armor pieces)",
            "",
            TextFormat::RESET . TextFormat::YELLOW . "This Armor is as strong as Diamond",
        ];

        if($head instanceof Armor && $chest instanceof Armor && $leggings instanceof Armor && $boots instanceof Armor) {
            $head->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->getColor() . "Demolition " . TextFormat::GRAY . "Helmet");
            $head->setOriginalLore($armorLore);
            $head->setCustomColor(new Color(139, 0, 0));
            $head->getNamedTag()->setString(SetManager::SET, "demolition");
            $chest->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->getColor() . "Demolition " . TextFormat::GRAY . "Chestplate");
            $chest->setOriginalLore($armorLore);
            $chest->setCustomColor(new Color(139, 0, 0));
            $chest->getNamedTag()->setString(SetManager::SET, "demolition");
            $leggings->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->getColor() . "Demolition " . TextFormat::GRAY . "Leggings");
            $leggings->setOriginalLore($armorLore);
            $leggings->setCustomColor(new Color(139, 0, 0));
            $leggings->getNamedTag()->setString(SetManager::SET, "demolition");
            $boots->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . $this->getColor() . "Demolition " . TextFormat::GRAY .  "Boots");
            $boots->setOriginalLore($armorLore);
            $leggings->setCustomColor(new Color(139, 0, 0));
            $boots->getNamedTag()->setString(SetManager::SET, "demolition");
        }

        return [$head, $chest, $leggings, $boots];
    }

    /**
     * @param EarnEnergyEvent $event
     */
    public function onEnergyGain(EarnEnergyEvent $event) : void
    {
        $player = $event->getPlayer();

        if($player instanceof NexusPlayer && SetUtils::isWearingFullSet($player, "demolition")) {
            $event->addAmount((int)ceil($event->getAmount() * 0.5));
        }
    }

    /**
     * @param BlockBreakEvent $event
     * @priority HIGHEST
     */
    public function onBreak(BlockBreakEvent $event) : void
    {
        $drops = [];

        if(!$event->isCancelled() && in_array($event->getBlock()->asItem()->getId(), [BlockLegacyIds::COAL_ORE, BlockLegacyIds::GOLD_ORE, BlockLegacyIds::IRON_ORE, BlockLegacyIds::REDSTONE_ORE, BlockLegacyIds::LAPIS_ORE, BlockLegacyIds::DIAMOND_ORE, BlockLegacyIds::EMERALD_ORE, BlockLegacyIds::NETHER_QUARTZ_ORE, ItemIds::PRISMARINE]) && Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotInPosition($event->getPlayer()->getPosition()) !== null) {
            if(SetUtils::isWearingFullSet($event->getPlayer(), "demolition")) {
                foreach ($event->getDrops() as $drop) {
                    $count = $drop->getCount();
                    $newCount = $count * 1.2;
                    $drop->setCount((int)ceil(($count + $newCount)));
                    $drops[] = $drop;
                }
            }
        }

        if(!empty($drops)) $event->setDrops($drops);
    }
}