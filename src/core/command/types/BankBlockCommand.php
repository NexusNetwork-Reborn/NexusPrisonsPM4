<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\forms\BankBlockMainForm;
use core\command\forms\ShopForm;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class BankBlockCommand extends Command {

    /**
     * BankBlockCommand constructor.
     */
    public function __construct() {
        parent::__construct("bankblock", "Manage your bank block.", "/bankblock", ["bb"]);
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
        if($sender->isLoaded()) {
            $sender->sendForm(new BankBlockMainForm($sender));
        }
    }
}