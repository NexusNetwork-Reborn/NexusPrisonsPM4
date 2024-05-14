<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\SubCommand;
use core\player\gang\command\forms\FlagsMenuForm;
use core\player\gang\Gang;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class FlagsSubCommand extends SubCommand {

    /**
     * FlagsSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("flags", "/gang flags");
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
        $gang = $sender->getDataSession()->getGang();
        if($gang === null) {
            $sender->sendTranslatedMessage("beInGang");
            return;
        }
        if($sender->getDataSession()->getGangRole() !== Gang::LEADER) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        $sender->sendForm(new FlagsMenuForm($gang));
    }
}