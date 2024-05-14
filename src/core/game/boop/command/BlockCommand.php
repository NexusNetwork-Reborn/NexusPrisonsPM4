<?php

declare(strict_types=1);

namespace core\game\boop\command;

use core\command\utils\args\TargetArgument;
use core\command\utils\args\TextArgument;
use core\command\utils\Command;
use core\translation\Translation;
use core\translation\TranslationException;
use core\game\boop\PunishmentEntry;
use core\game\boop\BOOPException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class BlockCommand extends Command {

    /**
     * BlockCommand constructor.
     */
    public function __construct() {
        parent::__construct("block", "Block a player", "/block <player> <reason>");
        $this->registerArgument(0, new TargetArgument("player"));
        $this->registerArgument(1, new TextArgument("reason"));
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
            if(!$sender->hasPermission("permission.admin")) {
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
        $name = array_shift($args);
        if($this->getCore()->getGameManager()->getBOOPManager()->isBlocked($name)) {
            $sender->sendMessage(Translation::getMessage("alreadyBlocked", [
                "name" => TextFormat::YELLOW . $name,
            ]));
            return;
        }
        $reason = implode(" ", $args);
        if(strlen($reason) > 100) {
            $sender->sendMessage(Translation::getMessage("reasonTooLong"));
            return;
        }
        $player = $this->getCore()->getServer()->getPlayerByPrefix($name);
        if($player !== null) {
            $name = $player->getName();
        }
        $this->getCore()->getServer()->broadcastMessage(Translation::getMessage("blockBroadcast", [
            "name" => TextFormat::RED . $name,
            "effector" => TextFormat::DARK_RED . $sender->getName(),
            "reason" => TextFormat::YELLOW . "\"$reason\"",
            "time" => TextFormat::RED . "Forever"
        ]));
        $this->getCore()->getGameManager()->getBOOPManager()->punish($name, PunishmentEntry::BLOCK, $sender->getName(), $reason, 0);
    }
}