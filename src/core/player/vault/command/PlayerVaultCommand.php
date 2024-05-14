<?php

declare(strict_types = 1);

namespace core\player\vault\command;

use core\command\utils\Command;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\vault\forms\VaultListForm;
use core\translation\Translation;
use core\translation\TranslationException;
use core\player\vault\command\subCommands\ViewSubCommand;
use pocketmine\command\CommandSender;

class PlayerVaultCommand extends Command {

    /**
     * PlayerVaultCommand constructor.
     */
    public function __construct() {
        parent::__construct("playervault", "Manage vaults", "/pv", ["pv"]);
        $this->addSubCommand(new ViewSubCommand());
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
        if(Nexus::getInstance()->getServerManager()->getAreaManager()->canDamage($sender->getPosition()) and (!$sender->hasPermission("permission.mod"))) {
            $sender->sendTranslatedMessage("inWarzone");
            return;
        }
        if(isset($args[0])) {
            $amount = $args[0];
            if(!is_numeric($amount)) {
                $subCommand = $this->getSubCommand($args[0]);
                if($subCommand !== null) {
                    $subCommand->execute($sender, $commandLabel, $args);
                    return;
                }
                if($sender->hasPermission("permission.mod")) {
                    $sender->sendMessage(Translation::getMessage("usageMessage", [
                        "usage" => $this->getUsage() . " <id> or /pv view <player>"
                    ]));
                    return;
                }
                else {
                    $sender->sendMessage(Translation::getMessage("noPermission"));
                    return;
                }
            }
            $amount = (int)$amount;
            if($amount <= 0) {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            $vault = $sender->getDataSession()->getVaultById($amount);
            if($vault !== null) {
                $vault->getMenu()->send($sender);
                return;
            }
            else {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
        }
        if($sender->hasPermission("permission.mod")) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage() . " <id> or /pv view <player>"
            ]));
        }
        $sender->sendForm(new VaultListForm($sender));
    }
}