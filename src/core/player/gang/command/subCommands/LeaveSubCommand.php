<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\SubCommand;
use core\player\gang\Gang;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class LeaveSubCommand extends SubCommand {

    /**
     * LeaveSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("leave", "/gang leave");
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
        if($sender->getDataSession()->getGang() === null) {
            $sender->sendTranslatedMessage("beInGang");
            return;
        }
        if($sender->getDataSession()->getGangRole() === Gang::LEADER) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        foreach($sender->getDataSession()->getGang()->getOnlineMembers() as $player) {
            $player->sendTranslatedMessage("gangLeave", [
                "name" => TextFormat::AQUA . $sender->getName()
            ]);
        }
        $sender->getDataSession()->getGang()->removeMember($sender->getName());
    }
}