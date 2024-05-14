<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class ShowcaseCommand extends Command {

    /**
     * AddXPCommand constructor.
     */
    public function __construct() {
        parent::__construct("showcase", "Manage showcase", "/showcase [player]");
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
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(isset($args[0])) {
            $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
            if(!$player instanceof NexusPlayer) {
                $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                return;
            }
        }
        else {
            $player = $sender;
        }
        if(!$player->isLoaded()) {
            $sender->sendMessage(Translation::getMessage("errorOccurred"));
            return;
        }
        $player->getDataSession()->getShowcase()->send($sender);
    }
}