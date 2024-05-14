<?php

namespace core\game\boop\command;

use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use core\game\boop\command\forms\PunishMenuForm;
use pocketmine\command\CommandSender;

class PunishCommand extends Command {

    /**
     * AuctionHouseCommand constructor.
     */
    public function __construct() {
        parent::__construct("punish", "Open punish menu", "/punish");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof NexusPlayer and $sender->hasPermission("permission.staff")) {
            $sender->sendForm(new PunishMenuForm());
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
        return;
    }
}