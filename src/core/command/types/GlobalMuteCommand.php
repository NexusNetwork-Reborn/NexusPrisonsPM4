<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class GlobalMuteCommand extends Command {

    /**
     * GlobalMuteCommand constructor.
     */
    public function __construct() {
        parent::__construct("globalmute", "Globally mute the server");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender->hasPermission(DefaultPermissions::ROOT_OPERATOR) or $sender instanceof ConsoleCommandSender or $sender->hasPermission("permission.admin")) {
            if($this->getCore()->isGlobalMuted()) {
                Server::getInstance()->broadcastMessage(TextFormat::BOLD . TextFormat::GREEN . "GLOBAL MUTE HAS BEEN TOGGLED OFF! YOU MAY CHAT NOW!");
                $this->getCore()->setGlobalMute(false);
                return;
            }
            Server::getInstance()->broadcastMessage(TextFormat::BOLD . TextFormat::RED . "GLOBAL MUTE HAS BEEN TOGGLED ON! ONLY STAFF MEMBERS CAN CHAT!");
            $this->getCore()->setGlobalMute(true);
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}