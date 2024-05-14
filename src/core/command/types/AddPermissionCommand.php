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
use pocketmine\command\ConsoleCommandSender;
use pocketmine\permission\DefaultPermissions;

class AddPermissionCommand extends Command {

    /**
     * AddPermissionCommand constructor.
     */
    public function __construct() {
        parent::__construct("addpermission", "Add a permission to a player.", "/addpermission <player> <permission>");
        $this->registerArgument(0, new TargetArgument("player"));
        $this->registerArgument(1, new RawStringArgument("permission"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(($sender->hasPermission(DefaultPermissions::ROOT_OPERATOR) and $sender instanceof NexusPlayer) or $sender instanceof ConsoleCommandSender) {
            if(isset($args[1])) {
                $player = $sender->getServer()->getPlayerByPrefix($args[0]);
                if($player instanceof NexusPlayer) {
                    $player->getDataSession()->addPermanentPermission((string)$args[1]);
                    $sender->sendMessage(Translation::getMessage("addPermission", [
                        "permission" => $args[1],
                        "name" => $player->getName()
                    ]));
                    return;
                }
                $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                return;
            }
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
        return;
    }
}