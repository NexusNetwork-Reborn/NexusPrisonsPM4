<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use core\game\item\sets\utils\SetUtils;
use core\game\plots\PlotManager;
use core\player\NexusPlayer;
use core\player\PlayerManager;
use pocketmine\block\BlockLegacyIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemIds;
use pocketmine\item\TieredTool;
use pocketmine\item\ToolTier;

class MomentumEnchantment extends Enchantment {

//    const COMPATIBILITY = [
//        ItemIds::WOODEN_PICKAXE => [BlockLegacyIds::COAL_ORE, BlockLegacyIds::IRON_ORE],
//        ItemIds::STONE_PICKAXE => [BlockLegacyIds::IRON_ORE, BlockLegacyIds::LAPIS_ORE],
//        ItemIds::GOLD_PICKAXE => [BlockLegacyIds::REDSTONE_ORE],
//        ItemIds::IRON_PICKAXE => [BlockLegacyIds::GOLD_ORE],
//        ItemIds::DIAMOND_PICKAXE => [BlockLegacyIds::DIAMOND_ORE],
//    ];

    private static $COMPATIBILITY = [];

    /**
     * MomentumEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::MOMENTUM, "Momentum", self::LEGENDARY, "The longer you mine, the faster you mine.", self::BREAK, self::SLOT_PICKAXE, 6);
        self::$COMPATIBILITY = [
            ToolTier::WOOD()->name() => [BlockLegacyIds::COAL_ORE, BlockLegacyIds::IRON_ORE],
            ToolTier::STONE()->name() => [BlockLegacyIds::IRON_ORE, BlockLegacyIds::LAPIS_ORE],
            ToolTier::GOLD()->name() => [BlockLegacyIds::REDSTONE_ORE],
            ToolTier::IRON()->name() => [BlockLegacyIds::GOLD_ORE],
            ToolTier::DIAMOND()->name() => [BlockLegacyIds::DIAMOND_ORE, BlockLegacyIds::EMERALD_ORE],
        ];
        $this->callable = function(BlockBreakEvent $event, int $level) {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($player->getCESession()->hasExplode()) {
                return;
            }
            /** @var TieredTool $item */
            // We do this because someone someone enchanted a fucking block with Momentum Lmao
            $item = $event->getItem();
            if(!($item instanceof TieredTool)){
                return;
            }
            if(!isset(self::$COMPATIBILITY[$item->getTier()->name()])) {
                return;
            }

            $block = $event->getBlock();
            $breakTime = PlayerManager::calculateBlockBreakTime($player, $block);
            $add = ceil($breakTime / 20); //TODO: Balance

            if(in_array($block->getId(), self::$COMPATIBILITY[$item->getTier()->name()])) {
                $add += mt_rand(2, 4);
            }
            if(PlotManager::isPlotWorld($player->getWorld())) {
                $add *= 0.75;
            }

            $player->getCESession()->addMomentum((int)$add);
        };
    }
}