<?php

declare(strict_types = 1);

namespace core\game\gamble\command;

use core\command\utils\Command;
use core\game\gamble\command\subCommands\BuySubCommand;
use core\game\gamble\GambleManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class LotteryCommand extends Command {

    /**
     * LotteryCommand constructor.
     */
    public function __construct() {
        parent::__construct("lottery", "Manage jackpot", "/lottery <buy> <amount>", ["jp", "jackpot"]);
        $this->addSubCommand(new BuySubCommand());
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
        if(isset($args[0])) {
            $subCommand = $this->getSubCommand($args[0]);
            if($subCommand !== null) {
                $subCommand->execute($sender, $commandLabel, $args);
                return;
            }
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $sender->sendMessage(" ");
        $sender->sendMessage(Utils::centerAlignText(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Jackpot", 60));
        $manager = $this->getCore()->getGameManager()->getGambleManager();
        $own = $manager->getDrawsFor($sender);
        $tickets = $manager->getTotalDraws();
        $total = $tickets * GambleManager::TICKET_PRICE;
        $total *= 0.9;
        $total = (int)floor($total);
        if($tickets === 0) {
            $percentage = 0;
        }
        else {
            $percentage = $own / $tickets;
            $percentage = round($percentage * 100, 2);
        }
        $sender->sendMessage(" ");
        $sender->sendMessage(TextFormat::BOLD . TextFormat::AQUA . "   Jackpot Value: " . TextFormat::WHITE . number_format($total) . TextFormat::AQUA . " Energy "  . TextFormat::RESET . TextFormat::GRAY . "(-10% tax)");
        $sender->sendMessage(TextFormat::BOLD . TextFormat::AQUA . "   Tickets Sold: " . TextFormat::RESET . TextFormat::YELLOW . number_format($tickets));
        $sender->sendMessage(TextFormat::BOLD . TextFormat::AQUA . "   Your Tickets: " . TextFormat::RESET . TextFormat::GREEN . number_format($own) . TextFormat::GRAY . "($percentage%)");
        $sender->sendMessage(" ");
        $sender->sendMessage(TextFormat::BOLD . TextFormat::AQUA . "   (!) " . TextFormat::RESET . TextFormat::AQUA . "Next winner in " . Utils::secondsToTime($this->getCore()->getGameManager()->getGambleManager()->getDrawer()->getTimeLeft()));
        $sender->sendMessage(" ");
        $sender->sendMessage(Translation::getMessage("usageMessage", [
            "usage" => $this->getUsage()
        ]));
        return;
    }
}