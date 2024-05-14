<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\inventory\WarpMenuInventory;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class ExecutiveCommand extends Command {

    /**
     * RestedCommand constructor.
     */
    public function __construct() {
        parent::__construct("executive", "Check your current Executive Mine goal.", "/executive", []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $task = WarpMenuInventory::getExecutiveSession($sender);
        if($task == null) {
            $sender->sendMessage(Translation::getMessage("notExecutive"));
        } else {
            $sender->sendMessage($task->getGoalMessage());
        }
    }
}