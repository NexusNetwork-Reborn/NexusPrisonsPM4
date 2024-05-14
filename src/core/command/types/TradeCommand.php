<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\game\trade\TradeSession;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class TradeCommand extends Command {

    /**
     * TradeCommand constructor.
     */
    public function __construct() {
        parent::__construct("trade", "Ask to trade with someone.", "/trade <player>");
        $this->registerArgument(0, new TargetArgument("player"));
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
        if(!isset($args[0])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
        if(!$player instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        if($player->isRequestingTrade($sender)) {
            foreach($this->getCore()->getGameManager()->getTradeManager()->getSessions() as $session) {
                if($session->getSender()->getUniqueId()->toString() === $player->getUniqueId()->toString()) {
                    $sender->sendMessage(Translation::getMessage("alreadyTrading", [
                        "name" => "{$player->getName()}"
                    ]));
                    return;
                }
                if($session->getReceiver()->getUniqueId()->toString() === $player->getUniqueId()->toString()) {
                    $sender->sendMessage(Translation::getMessage("alreadyTrading", [
                        "name" => "{$player->getName()}"
                    ]));
                    return;
                }
            }
            $player->removeTradeRequest($sender);
            $player->sendMessage(Translation::getMessage("acceptRequest"));
            $session = new TradeSession($player, $sender);
            $this->getCore()->getGameManager()->getTradeManager()->addSession($session);
            $session->sendMenus();
            return;
        }
        $sender->addTradeRequest($player);
        $sender->sendMessage(Translation::getMessage("requestTrade", [
            "name" => "You have",
            "player" => TextFormat::YELLOW . $player->getName()
        ]));
        $player->sendMessage(Translation::getMessage("requestTrade", [
            "name" => TextFormat::YELLOW . $sender->getName() . TextFormat::GRAY . " has",
            "player" => "you"
        ]));
    }
}