<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\RawStringArgument;
use core\command\utils\Command;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\Energy;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class GodCommand extends Command {

    /**
     * GodCommand constructor.
     */
    public function __construct() {
        parent::__construct("god", "Make THeRuTHLessCoW a god.", "/god");
        $this->registerArgument(0, new RawStringArgument("tier"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(in_array($sender->getName(), Nexus::SUPER_ADMIN)) {
            $sender->sendMessage(Translation::RED . TextFormat::RED . "You just got caught " . TextFormat::DARK_RED . "LACKING" . TextFormat::RED . ". Only someone under the username of " . TextFormat::YELLOW . "THeRuTHLessCoW" . TextFormat::RED . " can use this command.");
            return;
        }
        $enchantments = EnchantmentManager::getEnchantments();
        if(!isset($args[0])) {
            $args[0] = "diamond";
        }
        switch(strtolower($args[0])) {
            case "wooden":
            case "wood":
                $items = [
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_HELMET, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_CHESTPLATE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_LEGGINGS, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_BOOTS, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::WOODEN_SWORD, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::WOODEN_AXE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::BOW, 0, 1),
                ];
                break;
            case "stone":
                $items = [
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_HELMET, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_CHESTPLATE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_LEGGINGS, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_BOOTS, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::STONE_SWORD, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::STONE_AXE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::STONE_PICKAXE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::BOW, 0, 1),
                ];
                break;
            case "gold":
                $items = [
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_HELMET, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_CHESTPLATE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_LEGGINGS, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::CHAIN_BOOTS, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::GOLDEN_SWORD, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::GOLDEN_AXE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::GOLDEN_PICKAXE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::BOW, 0, 1),
                ];
                break;
            case "iron":
                $items = [
                    ItemFactory::getInstance()->get(ItemIds::IRON_HELMET, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::IRON_CHESTPLATE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::IRON_LEGGINGS, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::IRON_BOOTS, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::IRON_SWORD, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::IRON_AXE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::IRON_PICKAXE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::BOW, 0, 1),
                ];
                break;
            default:
                $items = [
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_AXE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE, 0, 1),
                    ItemFactory::getInstance()->get(ItemIds::BOW, 0, 1),
                ];
                break;
        }
        $newItems = [];
        /** @var Item $item */
        foreach($items as $item) {
            foreach($enchantments as $enchantment) {
                if(EnchantmentManager::canEnchant($item, $enchantment)) {
                    $item->addEnchantment(new EnchantmentInstance($enchantment, $enchantment->getMaxLevel()));
                }
            }
            foreach($enchantments as $enchantment) {
                if(EnchantmentManager::canEnchant($item, $enchantment)) {
                    $item->addEnchantment(new EnchantmentInstance($enchantment, $enchantment->getMaxLevel()));
                }
            }
            $newItems[] = $item;
        }
        if(isset($args[1]) and $args[1] === "b") {
            foreach($newItems as $item) {
                $sender->getInventory()->addItem($item->setDamage(700));
            }
            $sender->getInventory()->addItem((new Energy(10000000000))->toItem());
            return;
        }
        foreach($newItems as $item) {
            $sender->getInventory()->addItem($item);
        }
        $sender->getInventory()->addItem((new Energy(10000000000))->toItem());
    }
}