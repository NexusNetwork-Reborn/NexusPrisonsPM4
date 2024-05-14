<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\SubCommand;
use core\player\gang\Gang;
use core\player\gang\GangException;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class DisbandSubCommand extends SubCommand {

    /**
     * DisbandSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("disband", "/gang disband");
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
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $gang = $sender->getDataSession()->getGang();
        if($gang === null) {
            $sender->sendTranslatedMessage("beInGang");
            return;
        }
        if($sender->getDataSession()->getGangRole() !== Gang::LEADER) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        foreach($gang->getOnlineMembers() as $player) {
            $player->sendTitle(TextFormat::GREEN . TextFormat::BOLD . "Announcement", TextFormat::GRAY . $gang->getName() . " has been disbanded", 20, 60, 20);
        }
        $gang->disband();
    }
}