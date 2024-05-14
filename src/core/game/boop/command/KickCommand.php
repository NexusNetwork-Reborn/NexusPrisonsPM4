<?php

declare(strict_types=1);

namespace core\game\boop\command;

use core\command\utils\args\TargetArgument;
use core\command\utils\args\TextArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class KickCommand extends Command {

    /**
     * KickCommand constructor.
     */
    public function __construct() {
        parent::__construct("kick", "Kick a player", "/kick <player> <reason>");
        $this->registerArgument(0, new TargetArgument("player"));
        $this->registerArgument(1, new TextArgument("reason"));
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            if(!$sender->hasPermission("permission.staff")) {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
        }
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        /** @var NexusPlayer $player */
        $player = $this->getCore()->getServer()->getPlayerByPrefix(array_shift($args));
        if($player === null) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        if($player->isFrozen()) {
            $sender->sendMessage(Translation::getMessage("frozen", [
                "player" => TextFormat::AQUA . $player->getName()
            ]));
            return;
        }
        $reason = implode(" ", $args);
        $this->getCore()->getServer()->broadcastMessage(Translation::getMessage("kickBroadcast", [
            "name" => TextFormat::RED . $player->getName(),
            "effector" => TextFormat::DARK_RED . $sender->getName(),
            "reason" => TextFormat::YELLOW . "\"$reason\""
        ]));
        $player->kickDelay(Translation::getMessage("kickMessage", [
            "name" => TextFormat::RED . $sender->getName(),
            "reason" => TextFormat::YELLOW . $reason
        ]));
    }
}