<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\TargetArgument;
use core\command\utils\SubCommand;
use core\Nexus;
use core\player\gang\Gang;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ListSubCommand extends SubCommand {

    /**
     * ListSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("list", "/gang list");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        //$sender->sendMessage(TextFormat::YELLOW . 'Sorry, this command has some issues and is temporarily disabled until a fix can be made');

        //return;

        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $gangs = [];
        foreach($this->getCore()->getPlayerManager()->getGangManager()->getGangs() as $gang) {
            $gangs[$gang->getName()] = count($gang->getOnlineMembers());
        }
        arsort($gangs);
        $sender->sendMessage(" ");
        $text = TextFormat::GOLD . TextFormat::BOLD . "Gang Top Online List\n ";
        $top = array_slice($gangs, 0, (count($gangs) >= 10) ? 10 : count($gangs));
        $place = 0;
        $manager = Nexus::getInstance()->getPlayerManager()->getGangManager();
        foreach($top as $gang => $count) {
            ++$place;
            $members = count($manager->getGang($gang)->getMembers());
            $text .= "\n" . TextFormat::BOLD . TextFormat::GOLD . "$place. " . TextFormat::RESET . TextFormat::WHITE . $gang . TextFormat::GOLD . " - " . TextFormat::BOLD . TextFormat::GREEN . "$count online" . TextFormat::RESET . TextFormat::GRAY . " out of " . TextFormat::DARK_GREEN . $members;
        }
        $sender->sendMessage($text);
        $sender->sendMessage(" ");
    }
}