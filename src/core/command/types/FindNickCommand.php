<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\RawStringArgument;
use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class FindNickCommand extends Command {

    /**
     * FindNickCommand constructor.
     */
    public function __construct() {
        parent::__construct("findnick", "Find the real username of a nicknamed player.", "/findnick <player>", ["fnick"]);
        $this->registerArgument(0, new RawStringArgument("nickname"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(isset($args[0])) {
            $players = $this->getCore()->getPlayerManager()->getPlayersByNickname($args[0]);
            if(empty($players)) {
                $sender->sendMessage(Translation::RED . "Found zero results for $args[0]");
                return;
            }
            $sender->sendMessage(TextFormat::YELLOW . TextFormat::BOLD . "Results for $args[0]:");
            foreach($players as $nickname => $player) {
                $sender->sendMessage(TextFormat::GREEN . TextFormat::BOLD . "$nickname is known as " . TextFormat::RESET . TextFormat::WHITE . $player);
            }
            return;
        }
        $sender->sendMessage(Translation::getMessage("usageMessage", [
            "usage" => $this->getUsage()
        ]));
        return;
    }
}