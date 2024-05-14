<?php
declare(strict_types=1);

namespace core\game\plots\command\subCommands;

use core\command\utils\args\TargetArgument;
use core\command\utils\SubCommand;
use core\game\plots\plot\Plot;
use core\Nexus;
use core\player\gang\Gang;
use core\player\gang\PermissionManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class InviteSubCommand extends SubCommand {

    /**
     * InviteSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("invite", "/plot invite <player>", ["add"]);
        $this->registerArgument(0, new TargetArgument("player"));
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
        $plot = Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotByOwner($sender->getName());
        if($plot === null) {
            $sender->sendMessage(Translation::RED . "You must own a plot to invite someone!");
            return;
        }
        $owner = $plot->getOwner();
        if(count($owner->getUsers()) >= Plot::MAX_MEMBERS) {
            $sender->sendTranslatedMessage("gangMaxMembers", [
                "gang" => "Your plot"
            ]);
            return;
        }
        if(!isset($args[1])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[1]);
        if(!$player instanceof NexusPlayer) {
            $sender->sendTranslatedMessage("invalidPlayer");
            return;
        }
        if($owner->getUser($player->getName()) !== null) {
            $sender->sendMessage(Translation::getMessage("alreadyMember"));
            return;
        }
        $owner->addUser($player);
        $player->sendTranslatedMessage("plotGainAccess", [
            "plot" => TextFormat::WHITE . "Plot " . TextFormat::BOLD . TextFormat::AQUA . $plot->getId()
        ]);
        $sender->sendTranslatedMessage("plotGainAccessSender", [
            "name" => TextFormat::AQUA . $player->getName(),
            "plot" => TextFormat::WHITE . "Plot " . TextFormat::BOLD . TextFormat::AQUA . $plot->getId()
        ]);
    }
}