<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class BalanceCommand extends Command {

    /**
     * BalanceCommand constructor.
     */
    public function __construct() {
        parent::__construct("balance", "Show your or another player's balance.", "/balance [player]", ["bal", "mymoney", "seemoney"]);
        $this->registerArgument(0, new TargetArgument("player", true));
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
        $name = "Your";
        $balance = $sender->getDataSession()->getBalance();
        if(isset($args[0])) {
            $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
            if(!$player instanceof NexusPlayer) {
                $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT balance FROM stats WHERE username = ?");
                $stmt->bind_param("s", $args[0]);
                $stmt->execute();
                $stmt->bind_result($balance);
                $stmt->fetch();
                $stmt->close();
                if($balance === null) {
                    $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                    return;
                }
                $name = "$args[0]'s";
            }
            else {
                if(!$player->isLoaded()) {
                    $sender->sendMessage(Translation::getMessage("errorOccurred"));
                    return;
                }
                $name = $player->getName() . "'s";
                $balance = $player->getDataSession()->getBalance();
            }
        }
        $sender->sendMessage(Translation::getMessage("balance", [
            "name" => $name,
            "amount" => TextFormat::WHITE . "$" . number_format((int)$balance, 2)
        ]));
    }
}