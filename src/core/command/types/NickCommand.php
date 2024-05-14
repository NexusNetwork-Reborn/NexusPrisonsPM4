<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\RawStringArgument;
use core\command\utils\Command;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class NickCommand extends Command {

    /**
     * NickCommand constructor.
     */
    public function __construct() {
        parent::__construct("nick", "Set nick name.", "/nick <name>");
        $this->registerArgument(0, new RawStringArgument("name"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(($sender->hasPermission(DefaultPermissions::ROOT_OPERATOR) or $sender->hasPermission("permission.tier3")) and $sender instanceof NexusPlayer and (!$sender->isDisguise())) {
            if(isset($args[0])) {
                $name = (string)$args[0];
                if(strlen($name) > 16) {
                    $sender->sendMessage(Translation::getMessage("nicknameTooLong"));
                    return;
                }
                if(!ctype_alnum($args[0])) {
                    $sender->sendMessage(Translation::getMessage("onlyAlphanumeric"));
                    return;
                }
                if($this->getCore()->getPlayerManager()->nicknameExist($name)) {
                    $sender->sendMessage(Translation::RED . "There is already a player with this nickname!");
                    return;
                }
                $this->getCore()->getPlayerManager()->setNickname($sender, $name);
                $sender->sendMessage(Translation::GREEN . "You've successfully nicknamed yourself!");
                return;
            }
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
        $rankRequired = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::SUPREME);
        $sender->sendMessage(Translation::RED . "You must have " . $rankRequired->getColoredName() . TextFormat::RESET . TextFormat::GRAY . " Rank or up to use this command!");
    }
}