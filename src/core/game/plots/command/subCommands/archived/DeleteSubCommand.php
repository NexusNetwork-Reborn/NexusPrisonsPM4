<?php
declare(strict_types=1);

namespace core\game\plots\command\subCommands;

use core\command\task\TeleportTask;
use core\command\utils\SubCommand;
use core\Nexus;
use core\player\gang\GangException;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class DeleteSubCommand extends SubCommand {

    /**
     * DeleteSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("delete", "/plot delete <id>");
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
        $plot = $this->getCore()->getGameManager()->getPlotManager()->getPlot((int)$args[1]);
        if($plot !== null) {
            $sender->sendMessage("Deleting plot #" . $plot->getId());
            $this->getCore()->getGameManager()->getPlotManager()->deletePlot($plot);
        }
    }
}