<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\game\item\ItemManager;
use core\game\item\types\custom\SkinScroll;
use core\game\item\types\customies\DiamondSkinnedPickaxe;
use core\game\item\types\customies\DiamondSkinnedSword;
use core\game\item\types\customies\GoldSkinnedPickaxe;
use core\game\item\types\customies\GoldSkinnedSword;
use core\game\item\types\customies\IronSkinnedPickaxe;
use core\game\item\types\customies\IronSkinnedSword;
use core\game\item\types\customies\StoneSkinnedPickaxe;
use core\game\item\types\customies\StoneSkinnedSword;
use core\game\item\types\customies\WoodenSkinnedPickaxe;
use core\game\item\types\customies\WoodenSkinnedSword;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\command\CommandSender;
use pocketmine\item\Durable;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\ToolTier;

class RemoveItemSkinCommand extends Command {

    public function __construct() {
        parent::__construct("removeitemskin", "Extract item skin from your currently held item", "/removeitemskin", ["rmitemskin"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof NexusPlayer) {
            $item = $sender->getInventory()->getItemInHand();
            if (!($item instanceof Durable)) {
                $sender->sendMessage(Translation::getMessage("invalidItem"));
                return;
            }
            if (!self::hasSkin($item)) {
                $sender->sendMessage(Translation::getMessage("noSkin"));
                return;
            }
            $sender->sendMessage(Translation::getMessage("removeSkin"));
            $new = self::clearSkin($item);
            $sender->getInventory()->removeItem($sender->getInventory()->getItemInHand()->setCount(1));
            foreach ($new as $item) {
                if($item instanceof SkinScroll) {
                    $sender->getInventory()->addItem($item->toItem()->setCount(1));
                    continue;
                }
                $sender->getInventory()->addItem($item->setCount(1));
            }
        }
    }

    private const SKIN_CLASSES = [ // TODO: Axe
        WoodenSkinnedPickaxe::class, StoneSkinnedPickaxe::class, IronSkinnedPickaxe::class, GoldSkinnedPickaxe::class, DiamondSkinnedPickaxe::class,
        WoodenSkinnedSword::class, StoneSkinnedSword::class, IronSkinnedSword::class, GoldSkinnedSword::class, DiamondSkinnedSword::class
    ];

    public static function hasSkin(Durable $item) {
        return in_array(get_class($item), self::SKIN_CLASSES);
    }

    public static function clearSkin(Durable $item) : array {
        if($item instanceof Pickaxe) {
            $new = self::getPickaxeByTier($item->getTier());
            $skinScroll = ItemManager::getSkinScroll(ItemManager::getIdentifier($item->getId()));
            return [$item->copyInto($new), $skinScroll];
        }
        if($item instanceof Sword) {
            $new = self::getSwordByTier($item->getTier());
            $skinScroll = ItemManager::getSkinScroll(ItemManager::getIdentifier($item->getId()));
            return [$item->copyInto($new), $skinScroll];
        }
        // TODO: Sword and axe
        return [];
    }

    private static function getPickaxeByTier(ToolTier $tier) : ?Pickaxe {
        switch ($tier) {
            case ToolTier::WOOD():
                return ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE, 0, 1);
            case ToolTier::STONE():
                return ItemFactory::getInstance()->get(ItemIds::STONE_PICKAXE, 0, 1);
            case ToolTier::IRON():
                return ItemFactory::getInstance()->get(ItemIds::IRON_PICKAXE, 0, 1);
            case ToolTier::GOLD():
                return ItemFactory::getInstance()->get(ItemIds::GOLDEN_PICKAXE, 0, 1);
            case ToolTier::DIAMOND():
                return ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE, 0, 1);
        }
        return null;
    }

    private static function getSwordByTier(ToolTier $tier) : ?Sword {
        switch ($tier) {
            case ToolTier::WOOD():
                return ItemFactory::getInstance()->get(ItemIds::WOODEN_SWORD, 0, 1);
            case ToolTier::STONE():
                return ItemFactory::getInstance()->get(ItemIds::STONE_SWORD, 0, 1);
            case ToolTier::IRON():
                return ItemFactory::getInstance()->get(ItemIds::IRON_SWORD, 0, 1);
            case ToolTier::GOLD():
                return ItemFactory::getInstance()->get(ItemIds::GOLDEN_SWORD, 0, 1);
            case ToolTier::DIAMOND():
                return ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD, 0, 1);
        }
        return null;
    }

}