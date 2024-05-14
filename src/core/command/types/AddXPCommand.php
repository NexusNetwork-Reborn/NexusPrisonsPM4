<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\IntegerArgument;
use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class AddXPCommand extends Command {

    /**
     * AddXPCommand constructor.
     */
    public function __construct() {
        parent::__construct("addxp", "Add XP to a player", "/addxp <player> <amount>");
        $this->registerArgument(0, new TargetArgument("player"));
        $this->registerArgument(1, new IntegerArgument("amount"));
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
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
        if(!$player instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $amount = max(1, Utils::shortenToNumber($args[1]) !== null ? Utils::shortenToNumber($args[1]) : (int)$args[1]);
        $player->getDataSession()->addToXP($amount, false);
        $sender->sendMessage(Translation::getMessage("successAbuse"));
        return;
    }
}