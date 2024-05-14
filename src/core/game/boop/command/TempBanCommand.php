<?php

declare(strict_types=1);

namespace core\game\boop\command;

use core\command\utils\args\IntegerArgument;
use core\command\utils\args\TargetArgument;
use core\command\utils\args\TextArgument;
use core\command\utils\Command;
use core\translation\Translation;
use core\translation\TranslationException;
use core\game\boop\PunishmentEntry;
use core\game\boop\BOOPException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class TempBanCommand extends Command {

    /**
     * TempBanCommand constructor.
     */
    public function __construct() {
        parent::__construct("tempban", "Temporarily ban a player", "/tempban <player> <time> <reason>");
        $this->registerArgument(0, new TargetArgument("player"));
        $this->registerArgument(1, new IntegerArgument("time"));
        $this->registerArgument(2, new TextArgument("reason"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     * @throws BOOPException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            if(!$sender->hasPermission("permission.mod")) {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
        }
        if(!isset($args[2])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $name = array_shift($args);
        if($this->getCore()->getGameManager()->getBOOPManager()->isBanned($name)) {
            $sender->sendMessage(Translation::getMessage("alreadyBanned", [
                "name" => TextFormat::YELLOW . $name,
            ]));
            return;
        }
        $seconds = array_shift($args);
        if((!is_numeric($seconds))) {
            $seconds = Utils::stringToTime($seconds);
            if($seconds === null) {
                $sender->sendMessage(Translation::getMessage("invalidAmount"));
                return;
            }
        }
        $seconds = (int)$seconds;
        $reason = implode(" ", $args);
        if(strlen($reason) > 100) {
            $sender->sendMessage(Translation::getMessage("reasonTooLong"));
            return;
        }
        $timeString = Utils::secondsToTime($seconds);
        $player = $this->getCore()->getServer()->getPlayerByPrefix($name);
        if($player !== null) {
            $name = $player->getName();
            $player->kickDelay(Translation::getMessage("banMessage", [
                "name" => TextFormat::RED . $sender->getName(),
                "reason" => TextFormat::YELLOW . $reason,
                "time" => TextFormat::RED . $timeString
            ]));
        }
        $this->getCore()->getServer()->broadcastMessage(Translation::getMessage("banBroadcast", [
            "name" => TextFormat::RED . $name,
            "effector" => TextFormat::DARK_RED . $sender->getName(),
            "reason" => TextFormat::YELLOW . "\"$reason\"",
            "time" => TextFormat::RED . $timeString
        ]));
        $this->getCore()->getGameManager()->getBOOPManager()->punish($name, PunishmentEntry::BAN, $sender->getName(), $reason, $seconds);
    }
}