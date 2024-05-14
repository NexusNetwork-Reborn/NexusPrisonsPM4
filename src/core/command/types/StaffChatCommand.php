<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class StaffChatCommand extends Command {

    /**
     * StaffChatCommand constructor.
     */
    public function __construct() {
        parent::__construct("staffchat", "Toggle staff chat.", "/staffchat", ["sc"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender instanceof NexusPlayer) or (!$sender->hasPermission("permission.staff"))) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $mode = NexusPlayer::PUBLIC;
        if($sender->getChatMode() !== NexusPlayer::STAFF) {
            $mode = NexusPlayer::STAFF;
        }
        $sender->setChatMode($mode);
        $sender->sendTranslatedMessage("chatModeSwitch", [
            "mode" =>  TextFormat::AQUA . TextFormat::BOLD . strtoupper($sender->getChatModeToString())
        ]);
    }
}