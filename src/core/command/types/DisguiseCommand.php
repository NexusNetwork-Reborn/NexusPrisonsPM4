<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\CommandManager;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class DisguiseCommand extends Command {

    /**
     * DisguiseCommand constructor.
     */
    public function __construct() {
        parent::__construct("disguise", "Remove yourself from /list", "/disguise");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender instanceof NexusPlayer) or ((!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) and (!$sender->hasPermission("permission.staff")))) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if($sender->isDisguise()) {
            $this->getCore()->getCommandManager()->removeUsedDisguise($sender->getDisplayName());
            $sender->setDisplayName($sender->getName());
            $sender->setDisguiseRank(null);
            $sender->setDisguise(false);
            $sender->sendMessage(Translation::getMessage("disguiseOff"));
        }
        else {
            $sender->setDisguise(true);
            $disguise = $this->getCore()->getCommandManager()->selectDisguise();
            if($disguise === null) {
                $sender->sendMessage(Translation::getMessage("noNames"));
                return;
            }
            $sender->setDisplayName($disguise);
            $rank = $this->getCore()->getPlayerManager()->getRankManager()->getRankByIdentifier(CommandManager::DISGUISES[$disguise]);
            $sender->setDisguiseRank($rank);
            $sender->sendMessage(Translation::getMessage("disguiseOn", [
                "name" => TextFormat::YELLOW . $disguise,
                "rank" => $rank->getColoredName()
            ]));
        }
    }
}