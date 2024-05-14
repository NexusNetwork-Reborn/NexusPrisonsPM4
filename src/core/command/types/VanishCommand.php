<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class VanishCommand extends Command {

    /**
     * VanishCommand constructor.
     */
    public function __construct() {
        parent::__construct("vanish", "Enable or disable vanish", "/vanish <on/off>");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender instanceof NexusPlayer) or ((!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) and (!$sender->hasPermission("permission.mod")))) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!isset($args[0])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        switch($args[0]) {
            case "on":
                $sender->vanish();
                $sender->sendMessage(Translation::getMessage("vanishToggle"));
                break;
            case "off":
                $sender->vanish(false);
                $sender->sendMessage(Translation::getMessage("vanishToggle"));
                break;
            default:
                $sender->sendMessage(Translation::getMessage("usageMessage", [
                    "usage" => $this->getUsage()
                ]));
        }
    }
}