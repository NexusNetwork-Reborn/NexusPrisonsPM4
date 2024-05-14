<?php

declare(strict_types = 1);

namespace core\game\gamble\command;

use core\command\utils\args\IntegerArgument;
use core\command\utils\Command;
use core\game\gamble\command\forms\CoinFlipListForm;
use core\game\gamble\command\inventory\SelectColorInventory;
use core\game\gamble\command\subCommands\AddSubCommand;
use core\game\gamble\command\subCommands\CancelSubCommand;
use core\game\gamble\command\subCommands\ListSubCommand;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;

class CoinFlipCommand extends Command {

    /**
     * CoinFlipCommand constructor.
     */
    public function __construct() {
        parent::__construct("coinflip", "Manage coin flipping", "/cf [amount]", ["cf"]);
        $this->addSubCommand(new CancelSubCommand());
        $this->registerArgument(0, new IntegerArgument("amount"));
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
        if($this->getCore()->getGameManager()->getGambleManager()->getActiveCoinFlip($sender) !== null) {
            $sender->sendTranslatedMessage("activeCoinFlip");
            return;
        }
        if(isset($args[0])) {
            $subCommand = $this->getSubCommand($args[0]);
            if($subCommand !== null) {
                $subCommand->execute($sender, $commandLabel, $args);
                return;
            }
            else {
                if($this->getCore()->getGameManager()->getGambleManager()->getCoinFlip($sender) !== null) {
                    $sender->sendTranslatedMessage("existingCoinFlip");
                    return;
                }
                $amount = Utils::shortenToNumber($args[0]) !== null ? Utils::shortenToNumber($args[0]) : (int)$args[0];
                if($amount <= 0) {
                    $sender->sendTranslatedMessage("invalidAmount");
                    return;
                }
                if($sender->getDataSession()->getBalance() < $amount) {
                    $sender->sendTranslatedMessage("notEnoughMoney");
                    return;
                }
                $selectColor = new SelectColorInventory($amount);
                $selectColor->send($sender);
            }
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $sender->sendForm(new CoinFlipListForm($sender));
    }
}