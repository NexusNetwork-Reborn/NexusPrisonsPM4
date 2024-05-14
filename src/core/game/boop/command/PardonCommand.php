<?php

declare(strict_types=1);

namespace core\game\boop\command;

use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class PardonCommand extends Command {

    /**
     * PardonCommand constructor.
     */
    public function __construct() {
        parent::__construct("pardon", "Relieve a player from a ban", "/pardon <player>");
        $this->registerArgument(0, new TargetArgument("player"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            if(!$sender->hasPermission("permission.admin")) {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
        }
        if(!isset($args[0])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $name = $args[0];
        if(!$this->getCore()->getGameManager()->getBOOPManager()->isBanned($name)) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $this->getCore()->getServer()->broadcastMessage(Translation::getMessage("punishmentRelivedBroadcast", [
            "name" => TextFormat::AQUA . $name,
            "effector" => TextFormat::DARK_AQUA . $sender->getName(),
        ]));
        $this->getCore()->getGameManager()->getBOOPManager()->relieve($this->getCore()->getGameManager()->getBOOPManager()->getBan($name), $sender->getName());
    }
}