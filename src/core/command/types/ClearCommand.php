<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class ClearCommand extends Command {

    /**
     * ClearCommand constructor.
     */
    public function __construct() {
        parent::__construct("clear", "Clear inventory.", "/clear [player]");
        $this->registerArgument(0, new TargetArgument("player", true));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) or (!$sender instanceof NexusPlayer)) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(isset($args[0])) {
            $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
            if(!$player instanceof NexusPlayer) {
                $sender->sendTranslatedMessage("invalidPlayer");
                return;
            }
        }
        if(isset($player)) {
            $player->getInventory()->clearAll();
        }
        else {
            $sender->getInventory()->clearAll();
        }
        return;
    }
}