<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\forms\TagListForm;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class TitleCommand extends Command {

    /**
     * TagCommand constructor.
     */
    public function __construct() {
        parent::__construct("title", "Manage titles.");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof NexusPlayer) {
            $sender->sendForm(new TagListForm($sender));
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}