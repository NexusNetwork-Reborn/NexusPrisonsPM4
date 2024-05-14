<?php

namespace core\game\item\types\customies;

use core\game\item\types\vanilla\Pickaxe;
use customiesdevs\customies\item\component\DurabilityComponent;
use customiesdevs\customies\item\component\HandEquippedComponent;
use customiesdevs\customies\item\CreativeInventoryInfo;
use customiesdevs\customies\item\ItemComponents;
use customiesdevs\customies\item\ItemComponentsTrait;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ToolTier;
use pocketmine\nbt\tag\IntTag;

class WoodenSkinnedPickaxe extends Pickaxe implements ItemComponents {
    use ItemComponentsTrait;

    public function __construct(ItemIdentifier $identifier, string $name)
    {
        $tier = ToolTier::WOOD();

        parent::__construct($identifier->getId(), $identifier->getMeta(), $name, $tier);

        if(str_contains($name, "|")) {
            $args = explode("|", $name);
            $texture = $args[0];
            $name = $args[1];
        }
        if(!isset($texture)) {
            $texture = str_replace(" ", "_", strtolower($name));
        }
        $this->initComponent($texture, new CreativeInventoryInfo(CreativeInventoryInfo::CATEGORY_ITEMS));
        // TODO: Get the right names & offsets
        $this->addComponent(new DurabilityComponent($tier->getMaxDurability()));
        $this->addComponent(new HandEquippedComponent(true));
    }

    public function getAttackPoints(): int {
        return parent::getAttackPoints();
    }
}