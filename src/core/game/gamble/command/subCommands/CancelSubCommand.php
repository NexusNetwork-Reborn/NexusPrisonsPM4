<?php

declare(strict_types = 1);

namespace core\game\gamble\command\subCommands;

use core\command\utils\SubCommand;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class CancelSubCommand extends SubCommand {

    /**
     * CancelSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("cancel", "/coinflip cancel");
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
        $cf = $this->getCore()->getGameManager()->getGambleManager()->getCoinFlip($sender);
        if($cf === null) {
            $sender->sendMessage(Translation::getMessage("invalidCoinFlip"));
            return;
        }
        $sender->getDataSession()->addToBalance($cf->getAmount());
        $this->getCore()->getGameManager()->getGambleManager()->removeCoinFlip($sender);
    }
}