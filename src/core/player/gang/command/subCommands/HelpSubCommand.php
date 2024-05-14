<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\IntegerArgument;
use core\command\utils\SubCommand;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class HelpSubCommand extends SubCommand {

    /**
     * HelpSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("help", "/gang help <1-5>");
        $this->registerArgument(0, new IntegerArgument("page"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        switch($args[1]) {
            case 1:
                $sender->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "Gang Help " . TextFormat::RESET . TextFormat::DARK_GRAY . "(" . TextFormat::GREEN . "1/4" . TextFormat::DARK_GRAY . ")");
                $sender->sendMessage(TextFormat::YELLOW . " /gang ally " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Request to ally with a gang.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang announce " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Announce a message to the whole gang.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang chat " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Switch chatting modes.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang create " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Create a gang.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang demote " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Demote a gang member.");
                break;
            case 2:
                $sender->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "Gang Help " . TextFormat::RESET . TextFormat::DARK_GRAY . "(" . TextFormat::GREEN . "2/4" . TextFormat::DARK_GRAY . ")");
                $sender->sendMessage(TextFormat::YELLOW . " /gang deposit " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Deposit into your gang.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang disband " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Disband your gang.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang flags " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Manage your flags.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang info " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Show info about a gang.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang invite " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Invite a player.");
                break;
            case 3:
                $sender->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "Gang Help " . TextFormat::RESET . TextFormat::DARK_GRAY . "(" . TextFormat::GREEN . "3/4" . TextFormat::DARK_GRAY . ")");
                $sender->sendMessage(TextFormat::YELLOW . " /gang join " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Accept a gang invite.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang kick " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Kick a gang member.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang leader " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Give gang leadership to another gang member.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang leave " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Leave a gang.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang promote " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Promote a gang member.");
                break;
            case 4:
                $sender->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "Gang Help " . TextFormat::RESET . TextFormat::DARK_GRAY . "(" . TextFormat::GREEN . "4/4" . TextFormat::DARK_GRAY . ")");
                $sender->sendMessage(TextFormat::YELLOW . " /gang top " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Show top richest gangs.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang unally " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Remove an ally.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang vault " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Open gang vault.");
                $sender->sendMessage(TextFormat::YELLOW . " /gang withdraw " . TextFormat::GOLD . "- " . TextFormat::GRAY . "Withdraw from gang balance.");
                break;
            default:
                $sender->sendMessage(Translation::getMessage("usageMessage", [
                    "usage" => $this->getUsage()
                ]));
                break;
        }
    }
}
