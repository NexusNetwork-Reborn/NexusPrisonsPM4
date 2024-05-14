<?php
declare(strict_types=1);

namespace core\game\plots\command\subCommands;

use core\command\utils\args\TargetArgument;
use core\command\utils\SubCommand;
use core\game\plots\command\forms\PlotManageForm;
use core\game\plots\plot\Plot;
use core\Nexus;
use core\player\gang\Gang;
use core\player\gang\PermissionManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ManageSubCommand extends SubCommand {

    /**
     * ManageSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("manage", "/plot manage");
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
        $sender->sendForm(new PlotManageForm($sender));
    }
}