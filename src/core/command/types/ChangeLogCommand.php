<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\forms\ChangeLogForm;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class ChangeLogCommand extends Command {

    /**
     * ChangeLogCommand constructor.
     */
    public function __construct() {
        parent::__construct("changelog", "Open change log");
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
        $sender->sendForm(new ChangeLogForm());
    }
}