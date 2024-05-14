<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\forms\WithdrawForm;
use core\command\utils\args\IntegerArgument;
use core\command\utils\Command;
use core\game\item\types\custom\MoneyNote;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;

class WithdrawCommand extends Command {

    /**
     * WithdrawCommand constructor.
     */
    public function __construct() {
        parent::__construct("withdraw", "Withdraw your money.", "/withdraw <amount>");
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
        if($sender instanceof NexusPlayer) {
            if(isset($args[0])) {
                $amount = Utils::shortenToNumber($args[0]) !== null ? Utils::shortenToNumber($args[0]) : (float)$args[0];
                if($amount <= 0 or $sender->getDataSession()->getBalance() < $amount) {
                    $sender->sendMessage(Translation::getMessage("invalidAmount"));
                    return;
                }
                $money = (new MoneyNote($amount, $sender->getName()))->toItem();
                if(!$sender->getInventory()->canAddItem($money)) {
                    $sender->sendMessage(Translation::getMessage("inventoryFull"));
                    return;
                }
                $sender->getDataSession()->subtractFromBalance($amount);
                $sender->playDingSound();
                $sender->getInventory()->addItem($money);
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