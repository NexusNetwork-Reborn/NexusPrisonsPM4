<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\RawStringArgument;
use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class FreezeCommand extends Command {

    /**
     * FreezeCommand constructor.
     */
    public function __construct() {
        parent::__construct("freeze", "Freeze and unfreeze player.", "/freeze <on/off> <player>");
        $this->registerArgument(0, new RawStringArgument("on/off"));
        $this->registerArgument(1, new TargetArgument("player"));
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
            if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                if(!$sender->hasPermission("permission.staff")) {
                    $sender->sendMessage(Translation::getMessage("noPermission"));
                    return;
                }
            }
            switch($args[0]) {
                case "on":
                    if(!isset($args[1])) {
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage()
                        ]));
                        return;
                    }
                    $player = $this->getCore()->getServer()->getPlayerByPrefix($args[1]);
                    if(!$player instanceof NexusPlayer) {
                        $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                        return;
                    }
                    $player->setImmobile();
                    $player->setFrozen();
                    $player->sendMessage(Translation::getMessage("freezePlayer"));
                    $sender->sendMessage(Translation::getMessage("freezeSender", [
                        "player" => TextFormat::AQUA . $player->getName()
                    ]));
                    break;
                case "off":
                    if(!isset($args[1])) {
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage()
                        ]));
                        return;
                    }
                    $player = $this->getCore()->getServer()->getPlayerByPrefix($args[1]);
                    if(!$player instanceof NexusPlayer) {
                        $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                        return;
                    }
                    $player->setImmobile(false);
                    $player->setFrozen(false);
                    $player->sendMessage(Translation::getMessage("unfreezePlayer"));
                    $sender->sendMessage(Translation::getMessage("unfreezeSender", [
                        "player" => TextFormat::AQUA . $player->getName()
                    ]));
                    break;
            }
            return;
        }
        $sender->sendMessage(Translation::getMessage("usageMessage", [
            "usage" => $this->getUsage()
        ]));
        return;
    }
}