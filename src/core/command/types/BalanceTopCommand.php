<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\IntegerArgument;
use core\command\utils\Command;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class BalanceTopCommand extends Command {

    /**
     * BalanceTopCommand constructor.
     */
    public function __construct() {
        parent::__construct("balancetop", "Show the richest players.", "/balancetop [page = 1]", ["baltop", "topmoney"]);
        $this->registerArgument(0, new IntegerArgument("page", true));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(isset($args[0])) {
            if(!is_numeric($args[0])) {
                $sender->sendMessage(Translation::getMessage("invalidAmount"));
                return;
            }
            $page = (int)$args[0];
        }
        else {
            $page = 1;
        }
        $place = (($page - 1) * 10);
        $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT username, balance FROM stats ORDER BY balance DESC LIMIT 10 OFFSET " . $place);
        $stmt->execute();
        $stmt->bind_result($name, $balance);
        ++$place;
        $sender->sendMessage(" ");
        $text = TextFormat::GOLD . TextFormat::BOLD . "Player Top List " . TextFormat::RESET . TextFormat::GRAY . "Page $page\n ";
        while($stmt->fetch()) {
            $text .= "\n" . TextFormat::BOLD . TextFormat::GOLD . "$place. " . TextFormat::RESET . TextFormat::WHITE . $name . TextFormat::GOLD . " - " . TextFormat::GREEN . "$" . number_format($balance, 2);
            $place++;
        }
        $stmt->close();
        $sender->sendMessage($text);
        $sender->sendMessage(" ");
    }
}