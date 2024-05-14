<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\IntegerArgument;
use core\command\utils\SubCommand;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class TopSubCommand extends SubCommand {

    /**
     * TopSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("top", "/gang top [page = 1]");
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
        if(isset($args[1])) {
            if(!is_numeric($args[1])) {
                $sender->sendMessage(Translation::getMessage("invalidAmount"));
                $sender->sendMessage(Translation::getMessage("usageMessage", [
                    "usage" => $this->getUsage()
                ]));
                return;
            }
            $page = (int)$args[1];
        }
        else {
            $page = 1;
        }
        $place = (($page - 1) * 10);
        $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT name, value FROM gangs ORDER BY value DESC LIMIT 10 OFFSET " . $place);
        $stmt->execute();
        $stmt->bind_result($name, $balance);
        ++$place;
        $sender->sendMessage(" ");
        $text = TextFormat::GOLD . TextFormat::BOLD . "Gang Top List " . TextFormat::RESET . TextFormat::GRAY . "Page $page\n ";
        while($stmt->fetch()) {
            $text .= "\n" . TextFormat::BOLD . TextFormat::GOLD . "$place. " . TextFormat::RESET . TextFormat::WHITE . $name . TextFormat::GOLD . " - " . TextFormat::GREEN . number_format($balance) . TextFormat::BOLD . " REP";
            $place++;
        }
        $stmt->close();
        $sender->sendMessage($text);
        $sender->sendMessage(" ");
    }
}