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
use pocketmine\utils\TextFormat;

class PayCommand extends Command {

    /**
     * PayCommand constructor.
     */
    public function __construct() {
        parent::__construct("pay", "Pay a player.", "/pay <player> <amount>");
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
    public function execute(CommandSender $sender, $commandLabel, array $args): void {
        if($sender instanceof NexusPlayer) {
            if(isset($args[1])) {
                $player = $sender->getServer()->getPlayerByPrefix($args[0]);
                if(!$player instanceof NexusPlayer) {
                    $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                    return;
                }
                if($player->getName() === $sender->getName()) {
                    $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                    return;
                }
                if($player->isLoaded() === false) {
                    $sender->sendMessage(Translation::getMessage("errorOccurred"));
                    return;
                }
                $amount = Utils::shortenToNumber($args[1]) !== null ? Utils::shortenToNumber($args[1]) : (float)$args[1];
                if($amount <= 0) {
                    $sender->sendMessage(Translation::getMessage("invalidAmount"));
                    return;
                }
                if($sender->getDataSession()->getBalance() < $amount) {
                    $sender->sendMessage(Translation::getMessage("invalidAmount"));
                    return;
                }
                $sender->getDataSession()->subtractFromBalance($amount);
                $player->getDataSession()->addToBalance($amount);
                $sender->sendMessage(Translation::getMessage("payMoneyTo", [
                    "amount" => TextFormat::LIGHT_PURPLE . "$" . number_format($amount, 2),
                    "name" => TextFormat::AQUA . $player->getName()
                ]));
                $player->sendMessage(Translation::getMessage("receiveMoneyFrom", [
                    "amount" => TextFormat::LIGHT_PURPLE . "$" . number_format($amount, 2),
                    "name" => TextFormat::AQUA . $sender->getName()
                ]));
                return;
            }
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}