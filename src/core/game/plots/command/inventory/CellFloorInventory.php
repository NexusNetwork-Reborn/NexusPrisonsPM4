<?php

declare(strict_types=1);

namespace core\game\plots\command\inventory;

use core\game\plots\plot\Plot;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class CellFloorInventory extends InvMenu
{
    /**
     * CellFloorInventory Constructor
     *
     * @param Plot $cell
     */
    public function __construct(Plot $cell)
    {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenuTypeIds::TYPE_DOUBLE_CHEST));
        $this->initItems($cell);
        $this->setName(TextFormat::BOLD . TextFormat::DARK_GRAY . "Cell Floor: " . TextFormat::RESET . TextFormat::DARK_GRAY . $cell->getId());
        $this->setListener(self::readonly(function (DeterministicInvMenuTransaction $transaction) use($cell) : void {
            $player = $transaction->getPlayer();

            if(!$player instanceof NexusPlayer) return;

            if(Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotInPosition($transaction->getPlayer()->getPosition()) !== null) {
                if(Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotInPosition($transaction->getPlayer()->getPosition())->getOwner()->getUsername() === $transaction->getPlayer()->getName()) {
                    if($transaction->getItemClicked()->getId() === ItemIds::STAINED_GLASS_PANE) return;

                    $block = Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotInPosition($transaction->getPlayer()->getPosition())->getFloorBlocks()[0];

                    if($transaction->getItemClicked()->getId() === $block->asItem()->getId() && $transaction->getItemClicked()->getMeta() === $block->asItem()->getMeta()) {
                        InvMenuHandler::getPlayerManager()->get($transaction->getPlayer())->removeCurrentMenu();
                        $player->sendTranslatedMessage(Translation::getMessage("floorAlreadyBlock"));
                        return;
                    }

                    InvMenuHandler::getPlayerManager()->get($player)->removeCurrentMenu();
                    Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotInPosition($transaction->getPlayer()->getPosition())->setFloorBlocks(BlockFactory::getInstance()->get($transaction->getItemClicked()->getId(), $transaction->getItemClicked()->getMeta()));
                    $player->sendTranslatedMessage(Translation::getMessage("changedFloor", ["block" => $transaction->getItemClicked()->getVanillaName()]));
                } else {
                    InvMenuHandler::getPlayerManager()->get($transaction->getPlayer())->removeCurrentMenu();
                    $player->sendTranslatedMessage(Translation::getMessage("notPlotOwnerFloor"));
                }
            } else {
                InvMenuHandler::getPlayerManager()->get($transaction->getPlayer())->removeCurrentMenu();
                $player->sendTranslatedMessage(Translation::getMessage("notInPlotFloor"));
            }
        }));
    }

    /**
     * @param Plot $plot
     */
    public function initItems(Plot $plot) : void
    {
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 7);
        $glass->setCustomName("");

        for($i = 0; $i < $this->getInventory()->getSize(); $i++) {
            if($i <= 8 || $i >= 45 || in_array($i, [9, 10, 16, 17, 36, 37, 43, 44])) $this->getInventory()->setItem($i, $glass);
            if($i === 11) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::QUARTZ(), $plot));
            if($i === 12) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::STONE(), $plot));
            if($i === 13) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 15), $plot));
            if($i === 14) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 11), $plot));
            if($i === 15) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 12), $plot));
            if($i === 18) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 9), $plot));
            if($i === 19) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 7), $plot));
            if($i === 20) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 13), $plot));
            if($i === 21) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 3), $plot));
            if($i === 22) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 8), $plot));
            if($i === 23) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 5), $plot));
            if($i === 24) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 2), $plot));
            if($i === 25) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 1), $plot));
            if($i === 26) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 6), $plot));
            if($i === 27) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 10), $plot));
            if($i === 28) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 14), $plot));
            if($i === 29) $this->getInventory()->setItem($i, $this->convert(BlockFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, 0), $plot));
            if($i === 30) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::CRACKED_STONE_BRICKS(), $plot));
            if($i === 31) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::POLISHED_GRANITE(), $plot));
            if($i === 32) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::POLISHED_ANDESITE(), $plot));
            if($i === 33) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::POLISHED_DIORITE(), $plot));
            if($i === 34) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::GRANITE(), $plot));
            if($i === 35) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::DIORITE(), $plot));
            if($i === 38) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::ANDESITE(), $plot));
            if($i === 39) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::OBSIDIAN(), $plot));
            if($i === 40) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::GRASS(), $plot));
            if($i === 41) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::BRICKS(), $plot));
            if($i === 42) $this->getInventory()->setItem($i, $this->convert(VanillaBlocks::STONE_BRICKS(), $plot));
        }
    }

    /**
     * @param Block $block
     * @param Plot $cell
     * @return Item
     */
    public function convert(Block $block, Plot $cell) : Item
    {
        $item = $block->asItem();

        if($cell->getFloorBlocks()[0]->getIdInfo()->getBlockId() === $block->getIdInfo()->getBlockId() && $cell->getFloorBlocks()[0]->asItem()->getMeta() === $item->getMeta()) {
            // add empty enchantment if currently selected floor
            $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
        }

        $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . $item->getName());
        $item->setLore([
            "",
            TextFormat::RESET . TextFormat::GREEN . "(Click to select)"
        ]);

        return $item;
    }
}