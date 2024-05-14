<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class RestedCommand extends Command {

    /**
     * RestedCommand constructor.
     */
    public function __construct() {
        parent::__construct("rested", "Check rested xp.", "/rested", ["rxp"]);
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
        $sender->sendMessage(" ");
        if($sender->getDataSession()->getRestedXP() > 0) {
            $sender->sendMessage(TextFormat::BOLD . TextFormat::GREEN . "(!) Rested XP" . TextFormat::RESET . TextFormat::GREEN . " has " . Utils::secondsToTime($sender->getDataSession()->getRestedXP()) . " remaining - " . TextFormat::BOLD . "1.5x XP");
        }
        else {
            $sender->sendMessage(TextFormat::BOLD . TextFormat::RED . "(!) Rested XP" . TextFormat::RESET . TextFormat::RED . " has " . Utils::secondsToTime($sender->getDataSession()->getRestedXP()) . " remaining - " . TextFormat::BOLD . "1.0x XP");
        }
        $sender->sendMessage(TextFormat::GRAY . "You accrue Rested XP while logged out, and use it while being logged in.");
        $sender->sendMessage(" ");
    }
}