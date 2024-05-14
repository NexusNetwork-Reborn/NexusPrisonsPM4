<?php
declare(strict_types=1);

namespace core\game\plots\command\subCommands;

use core\command\utils\SubCommand;
use core\player\gang\GangException;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class CreateSubCommand extends SubCommand {

    /**
     * CreateSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("create", "/plot create");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws GangException
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) or (!$sender instanceof NexusPlayer)) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $this->getCore()->getGameManager()->getPlotManager()->addPlotCreateSession($sender);
        $sender->sendMessage("Started plot create session");
    }
}