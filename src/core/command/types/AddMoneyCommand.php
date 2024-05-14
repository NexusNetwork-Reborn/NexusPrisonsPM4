<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\IntegerArgument;
use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class AddMoneyCommand extends Command {

    /**
     * AddMoneyCommand constructor.
     */
    public function __construct() {
        parent::__construct("addmoney", "Add money to a player's balance.", "/addmoney <player> <amount>", ["givemoney"]);
        $this->registerArgument(0, new TargetArgument("player"));
        $this->registerArgument(1, new IntegerArgument("amount"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
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
        }
        $amount = Utils::shortenToNumber($args[1]) !== null ? Utils::shortenToNumber($args[1]) : (float)$args[1];
        if($amount <= 0) {
            $sender->sendMessage(Translation::getMessage("invalidAmount"));
            return;
        }
        if(isset($balance)) {
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("UPDATE stats SET balance = balance + ? WHERE username = ?");
            $stmt->bind_param("is", $amount, $args[0]);
            $stmt->execute();
            $stmt->close();
        }
        else {
            $player->getDataSession()->addToBalance($amount);
        }
        $sender->sendMessage(Translation::getMessage("addMoneySuccess", [
            "amount" => TextFormat::GREEN . "$" . number_format($amount, 2),
            "name" => TextFormat::GOLD . $player instanceof NexusPlayer ? $player->getName() : $args[0]
        ]));
    }
}