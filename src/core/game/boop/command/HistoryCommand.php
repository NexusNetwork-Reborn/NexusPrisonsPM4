<?php

declare(strict_types=1);

namespace core\game\boop\command;

use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\game\boop\command\forms\PunishHistoryForm;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class HistoryCommand extends Command {

    /**
     * HistoryCommand constructor.
     */
    public function __construct() {
        parent::__construct("history", "Check the history of a player", "/history <player>");
        $this->registerArgument(0, new TargetArgument("player"));
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            if(!$sender->hasPermission("permission.staff")) {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
        }
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!isset($args[0])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $player = $args[0];
        $entries = Nexus::getInstance()->getGameManager()->getBOOPManager()->getHistoryOf($player);
        $sender->sendForm(new PunishHistoryForm($entries));
    }
}