<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\RawStringArgument;
use core\command\utils\SubCommand;
use core\player\gang\GangException;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class CreateSubCommand extends SubCommand {

    /**
     * CreateSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("create", "/gang create <name>");
        $this->registerArgument(0, new RawStringArgument("name"));
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
        if($sender->getDataSession()->getGang() !== null) {
            $sender->sendTranslatedMessage("mustLeaveGang");
            return;
        }
        if(!isset($args[1])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        if(strlen($args[1]) > 16) {
            $sender->sendTranslatedMessage("gangNameTooLong");
            return;
        }
        if(!ctype_alnum($args[1]) || is_numeric($args[1])) {
            $sender->sendTranslatedMessage("onlyAlphanumeric");
            return;
        }
        $gang = $this->getCore()->getPlayerManager()->getGangManager()->getGang($args[1]);
        if($gang !== null) {
            $sender->sendTranslatedMessage("existingGang", [
                "gang" => TextFormat::RED . $args[1]
            ]);
            return;
        }
        $this->getCore()->getPlayerManager()->getGangManager()->createGang($args[1], $sender);
        $sender->sendTranslatedMessage("gangCreate");
        $sender->getDataSession()->saveDataAsync();
    }
}