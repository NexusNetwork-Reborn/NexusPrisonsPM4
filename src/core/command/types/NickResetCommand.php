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

class NickResetCommand extends Command {

    /**
     * NickResetCommand constructor.
     */
    public function __construct() {
        parent::__construct("nickreset", "Reset nick name.", "/nickreset");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof NexusPlayer) {
            $nick = $this->getCore()->getPlayerManager()->getNickname($sender);
            if($nick !== null) {
                $this->getCore()->getPlayerManager()->removeNickname($sender);
                $sender->sendMessage(Translation::GREEN . "Your nickname has been reset!");
                return;
            }
            $sender->sendMessage(Translation::GREEN . "You must have a nickname before resetting one!");
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
        $rankRequired = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::SUPREME);
        $sender->sendMessage(Translation::RED . "You must have " . $rankRequired->getColoredName() . TextFormat::RESET . TextFormat::GRAY . " Rank or up to use this command!");
    }
}