<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\SubCommand;
use core\player\gang\command\arguments\ChatTypeArgument;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ChatSubCommand extends SubCommand {

    /**
     * ChatSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("chat", "/gang chat [mode]", ["c"]);
        $this->registerArgument(0, new ChatTypeArgument("mode", true));
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
        if(isset($args[1])) {
            switch($args[1]) {
                case "p":
                case "public":
                    $mode = NexusPlayer::PUBLIC;
                    break;
                case "a":
                case "ally":
                    $mode = NexusPlayer::ALLY;
                    break;
                case "g":
                case "gang":
                    $mode = NexusPlayer::GANG;
                    break;
                default:
                    $mode = $sender->getChatMode() + 1;
                    break;
            }
        }
        else {
            $mode = $sender->getChatMode() + 1;
        }
        if($mode > 2) {
            $mode = 0;
        }
        $sender->setChatMode($mode);
        $sender->sendTranslatedMessage("chatModeSwitch", [
            "mode" => TextFormat::AQUA . TextFormat::BOLD . ($sender->getChatModeToString())
        ]);
    }
}