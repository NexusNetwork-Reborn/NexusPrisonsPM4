<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\game\fund\FundManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class MeteorTimerCommand extends Command {

    /**
     * MeteorTimerCommand constructor.
     */
    public function __construct() {
        parent::__construct("meteortimer", "Check how long until next meteor.", "/meteortimer");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender->hasPermission("permission.tier5")) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            $rankRequired = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::EMPEROR);
            $sender->sendMessage(Translation::RED . "You must have " . $rankRequired->getColoredName() . TextFormat::RESET . TextFormat::GRAY . " Rank or up to use this command!");
            return;
        }
        if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_ONE)) {
            $sender->sendTranslatedMessage("fundDisabled", [
                "feature" => TextFormat::RED . "Meteors"
            ]);
            return;
        }
        $date = Utils::secondsToTime($this->getCore()->getLevelManager()->getMeteorSpawner()->getTimeLeft());
        $sender->sendMessage(Translation::YELLOW . "Next meteor in: $date");
    }
}