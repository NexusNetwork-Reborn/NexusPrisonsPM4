<?php

declare(strict_types = 1);

namespace core\game\gamble\command\subCommands;

use core\command\utils\args\IntegerArgument;
use core\command\utils\SubCommand;
use core\game\gamble\event\LotteryBuyEvent;
use core\game\gamble\GambleManager;
use core\game\item\types\custom\Energy;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class BuySubCommand extends SubCommand {

    /**
     * BuySubCommand constructor.
     */
    public function __construct() {
        parent::__construct("buy", "/lottery buy <amount>");
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
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $tickets = Utils::shortenToNumber($args[1]) !== null ? (int)Utils::shortenToNumber($args[1]) : (int)$args[1];
        if((int)$tickets <= 0) {
            $sender->sendMessage(Translation::getMessage("notNumeric"));
            return;
        }
        $price = $tickets * GambleManager::TICKET_PRICE;
        $item = $sender->getInventory()->getItemInHand();
        if(!Energy::isInstanceOf($item)) {
            $sender->sendMessage(Translation::getMessage("invalidItem"));
            return;
        }
        $orb = Energy::fromItem($item);
        $energy = $orb->getEnergy();
        if($price > $energy) {
            $sender->sendMessage(Translation::getMessage("notEnoughEnergyRankUp", [
                "amount" => TextFormat::RED . number_format($price)
            ]));
            return;
        }
        $this->getCore()->getGameManager()->getGambleManager()->addDraws($sender, $tickets);
        $ev = new LotteryBuyEvent($sender, $tickets);
        $ev->call();
        $leftOver = $energy - $price;
        if($leftOver > 0) {
            $orb->setEnergy($leftOver);
            $sender->getInventory()->setItemInHand($orb->toItem());
        }
        else {
            $sender->getInventory()->setItemInHand(ItemFactory::getInstance()->get(ItemIds::AIR));
        }
        $sender->sendMessage(Translation::getMessage("buy", [
            "amount" => TextFormat::DARK_AQUA . "x" . number_format($tickets),
            "item" => TextFormat::AQUA . "Lottery Tickets",
            "price" => TextFormat::LIGHT_PURPLE . number_format($price),
        ]));
    }
}