<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\IntegerArgument;
use core\command\utils\Command;
use core\game\item\ItemManager;
use core\game\item\types\custom\XPBottle;
use core\game\item\types\Rarity;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;

class XPExtractCommand extends Command {

    /**
     * WithdrawCommand constructor.
     */
    public function __construct() {
        parent::__construct("xpextract", "Withdraw your player xp.", "/xpextract <amount>");
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
                $amount = Utils::shortenToNumber($args[0]) !== null ? Utils::shortenToNumber($args[0]) : (int)$args[0];
                if($amount <= 0 or $sender->getDataSession()->getXP() < $amount) {
                    $sender->sendMessage(Translation::getMessage("invalidAmount"));
                    return;
                }
                $xp = max(0, $sender->getDataSession()->getMaxSubtractableXP());
                if($xp <= 0) {
                    $sender->sendMessage(Translation::RED . "You can not withdraw any xp!");
                    return;
                }
                if($xp < $amount) {
                    $amount = $xp;
                }
                $amount = (int)$amount;
                $sender->playDingSound();
                $sender->getInventory()->addItem((new XPBottle(min($amount, $sender->getDataSession()->getMaxSubtractableXP()), ItemManager::getRarityForXPByLevel($sender->getDataSession()->getTotalXPLevel())))->toItem());
                $sender->getDataSession()->subtractFromXP($amount);
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