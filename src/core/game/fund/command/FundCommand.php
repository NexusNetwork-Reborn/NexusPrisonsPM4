<?php

declare(strict_types = 1);

namespace core\game\fund\command;

use core\command\forms\BankBlockMainForm;
use core\game\fund\command\forms\FundMainForm;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class FundCommand extends Command {

    /**
     * BankBlockCommand constructor.
     */
    public function __construct() {
        parent::__construct("fund", "Check global funding statuses.", "/fund");
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
        $sender->sendForm(new FundMainForm());
    }
}