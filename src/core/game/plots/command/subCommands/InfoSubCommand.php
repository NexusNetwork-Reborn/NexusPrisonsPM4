<?php
declare(strict_types=1);

namespace core\game\plots\command\subCommands;

use core\command\utils\args\TargetArgument;
use core\command\utils\SubCommand;
use core\game\plots\command\forms\PlotManageForm;
use core\game\plots\forms\PlotInfoForm;
use core\game\plots\plot\Plot;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\permission\DefaultPermissions;

class InfoSubCommand extends SubCommand {

    /**
     * ManageSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("info", "/plot info <plut_number>");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        if(!is_numeric($args[1])){
            $sender->sendTranslatedMessage(Translation::getMessage("notNumeric"));
            return;
        }
        $plot = Nexus::getInstance()->getGameManager()->getPlotManager()->getPlot((int)$args[1]);
        if($plot === null){
            $sender->sendTranslatedMessage(Translation::getMessage("plotNonExistent", [
                "plot" => $args[1]
            ]));
            return;
        }
        $sender->sendForm(new PlotInfoForm($plot));
    }
}