<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\RawStringArgument;
use core\command\utils\SubCommand;
use core\player\gang\GangException;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\permission\DefaultPermissions;

class ForceDeleteSubCommand extends SubCommand {

    /**
     * ForceDeleteSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("forcedelete", "/gang forcedelete <gang>");
        $this->registerArgument(0, new RawStringArgument("gang"));
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
        if((!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) and (!$sender instanceof ConsoleCommandSender)) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $gang = $this->getCore()->getPlayerManager()->getGangManager()->getGang($args[1]);
        if($gang === null) {
            $sender->sendMessage(Translation::getMessage("invalidGang"));
            return;
        }
        $gang->disband();
        $sender->sendMessage(Translation::getMessage("forceDelete"));
    }
}