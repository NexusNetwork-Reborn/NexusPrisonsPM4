<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\forms\VoteMenuForm;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class VoteCommand extends Command {

    /**
     * VoteCommand constructor.
     */
    public function __construct() {
        parent::__construct("vote", "Check if you've voted yet.", "/vote");
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
        $sender->sendForm(new VoteMenuForm());
    }
}