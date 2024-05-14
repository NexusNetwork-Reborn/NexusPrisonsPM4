<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\IntegerArgument;
use core\command\utils\SubCommand;
use core\player\gang\PermissionManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class DepositSubCommand extends SubCommand {

    /**
     * DepositSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("deposit", "/gang deposit <amount>", ["d"]);
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
        $gang = $sender->getDataSession()->getGang();
        if($gang === null) {
            $sender->sendTranslatedMessage("beInGang");
            return;
        }
        if(!$gang->getPermissionManager()->hasPermission($sender, PermissionManager::PERMISSION_DEPOSIT)) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        if(!isset($args[1])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        if($args[1] === "all") {
            $amount = $sender->getDataSession()->getBalance();
        }
        else {
            $amount = Utils::shortenToNumber($args[1]) !== null ? Utils::shortenToNumber($args[1]) : (int)$args[1];
        }
        $amount = (int)$amount;
        if($amount <= 0) {
            $sender->sendMessage(Translation::getMessage("invalidAmount"));
            return;
        }
        if($sender->getDataSession()->getBalance() < $amount) {
            $sender->sendTranslatedMessage("notEnoughMoney");
            return;
        }
        $sender->getDataSession()->subtractFromBalance($amount);
        $gang->addMoney($amount);
        foreach($sender->getDataSession()->getGang()->getOnlineMembers() as $member) {
            $member->sendTranslatedMessage("deposit", [
                "name" => TextFormat::AQUA . $sender->getName(),
                "amount" => TextFormat::LIGHT_PURPLE . "$$amount"
            ]);
        }
    }
}