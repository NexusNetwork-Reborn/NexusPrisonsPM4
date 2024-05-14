<?php

namespace core\server\inventory;

use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\type\util\InvMenuTypeBuilders;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

class InventoryTypes {

    public const TYPE_DISPENSER = "aethic:dispenser";

    public static function registerCustomMenuTypes(): void {
        /** @var BlockFactory $blockFactory */
        $blockFactory = BlockFactory::getInstance();
        InvMenuHandler::getTypeRegistry()->register(self::TYPE_DISPENSER, InvMenuTypeBuilders::BLOCK_ACTOR_FIXED()->setBlock($blockFactory->get(BlockLegacyIds::DISPENSER, 0))
            ->setBlockActorId("Dispenser")
            ->setSize(9)
            ->setNetworkWindowType(WindowTypes::DISPENSER)
            ->build());
    }
}