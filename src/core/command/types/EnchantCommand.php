<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\IntegerArgument;
use core\command\utils\args\RawStringArgument;
use core\command\utils\Command;
use core\game\item\enchantment\EnchantmentManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class EnchantCommand extends Command {

    /**
     * EnchantCommand constructor.
     */
    public function __construct() {
        parent::__construct("enchant", "Add an enchantment to an item", "/enchant <enchantment> <level>");
        $this->registerArgument(0, new RawStringArgument("enchantment"));
        $this->registerArgument(1, new IntegerArgument("level"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) or (!$sender instanceof NexusPlayer))  {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if (!in_array($sender->getName(), Nexus::SUPER_ADMIN)){
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $enchantment = EnchantmentManager::getEnchantment($args[0]);
        if($enchantment === null) {
            $sender->sendMessage(Translation::getMessage("invalidEnchantment"));
            return;
        }
        $level = (int)$args[1];
        if((!is_numeric($level)) or $enchantment->getMaxLevel() < $level) {
            $sender->sendMessage(Translation::getMessage("invalidAmount"));
            return;
        }
        $item = $sender->getInventory()->getItemInHand();
        if(EnchantmentManager::canEnchant($item, $enchantment) === false) {
            $sender->sendMessage(Translation::getMessage("invalidItem"));
            return;
        }
        $enchantment = new EnchantmentInstance($enchantment, $level);
        $item->addEnchantment($enchantment);
        $sender->getInventory()->setItemInHand($item);
        $sender->sendMessage(Translation::getMessage("successAbuse"));
        return;
    }
}