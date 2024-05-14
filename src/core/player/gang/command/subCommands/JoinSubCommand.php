<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\RawStringArgument;
use core\command\utils\SubCommand;
use core\player\gang\Gang;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class JoinSubCommand extends SubCommand {

    /**
     * JoinSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("join", "/gang join <gang>");
        $this->registerArgument(0, new RawStringArgument("gang"));
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
        if(!isset($args[1])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $gang = $this->getCore()->getPlayerManager()->getGangManager()->getGang($args[1]);
        if($gang === null) {
            $sender->sendTranslatedMessage("invalidGang");
            return;
        }
        if(!$gang->isInvited($sender)) {
            $sender->sendTranslatedMessage("notInvited", [
                "gang" => TextFormat::RED . $gang->getName()
            ]);
            return;
        }
        if(count($gang->getMembers()) >= Gang::MAX_MEMBERS) {
            $sender->sendTranslatedMessage("gangMaxMembers", [
                "gang" => TextFormat::RED . $gang->getName()
            ]);
            return;
        }
        if($sender->getDataSession()->getGang() !== null) {
            $sender->sendTranslatedMessage("mustLeaveGang");
            return;
        }
        $gang->addMember($sender);
        $gang->removeInvite($sender);
        foreach($gang->getOnlineMembers() as $member) {
            $member->sendTranslatedMessage("gangJoin", [
                "name" => TextFormat::AQUA . $sender->getName()
            ]);
        }
    }
}