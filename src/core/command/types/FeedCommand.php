<?php

namespace core\command\types;

use core\command\utils\Command;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class FeedCommand extends Command {

    /**
     * FeedCommand constructor.
     */
    public function __construct() {
        parent::__construct("feed", "Restore hunger");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender instanceof NexusPlayer) or ((!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) and (!$sender->hasPermission("permission.tier1")))) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            $rankRequired = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::NOBLE);
            $sender->sendMessage(Translation::RED . "You must have " . $rankRequired->getColoredName() . TextFormat::RESET . TextFormat::GRAY . " Rank or up to use this command!");
            return;
        }
        $sender->getHungerManager()->setFood(20);
        $sender->sendTranslatedMessage("hungerRestored");
    }
}