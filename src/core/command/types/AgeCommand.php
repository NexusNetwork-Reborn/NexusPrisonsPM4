<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\Nexus;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class AgeCommand extends Command {

    /**
     * AgeCommand constructor.
     */
    public function __construct() {
        parent::__construct("age", "Check how long the season has been out.", "/age");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $date = Utils::secondsToTime(time() - Nexus::START);
        $sender->sendMessage(TextFormat::AQUA . "The season has been out for $date.");
    }
}