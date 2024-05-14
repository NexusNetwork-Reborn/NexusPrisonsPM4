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

class InvalidateSubCommand extends SubCommand {

    /**
     * InvalidateSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("invalidate", "/plot invalidate");
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
        foreach($this->getCore()->getGameManager()->getPlotManager()->getPlots() as $plot) {
            if($plot->getSpawn()->equals($plot->getSecondPosition())) {
                var_dump($plot->getId());
            }
        }
        var_dump("done");
    }
}