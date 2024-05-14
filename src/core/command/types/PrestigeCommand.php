<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class PrestigeCommand extends Command {

    /**
     * PrestigeCommand constructor.
     */
    public function __construct() {
        parent::__construct("prestige", "Prestige.", "/prestige");
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
        $dataSession = $sender->getDataSession();
        $prestige = $dataSession->getPrestige();
        if($prestige >= RPGManager::MAX_PRESTIGE) {
            $sender->sendMessage(Translation::getMessage("maxPrestige"));
            return;
        }
        if($dataSession->getXP() < (XPUtils::levelToXP(100) + RPGManager::getPrestigeXP($prestige))) {
            $sender->sendMessage(Translation::getMessage("prestigeRequirement"));
            return;
        }
        if($dataSession->getBankBlock() > 0) {
            $sender->sendMessage(Translation::getMessage("clearBankBlock"));
            return;
        }
        $dataSession->prestige();
    }
}