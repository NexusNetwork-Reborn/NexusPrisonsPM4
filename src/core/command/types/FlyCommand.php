<?php

namespace core\command\types;

use core\command\utils\Command;
use core\faction\Faction;
use core\game\plots\PlotManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class FlyCommand extends Command {

    /**
     * FlyCommand constructor.
     */
    public function __construct() {
        parent::__construct("fly", "Modify flight mode");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender instanceof NexusPlayer) or ((!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) and (!$sender->hasPermission("permission.tier2")))) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            $rankRequired = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::EMPEROR_HEROIC);
            $sender->sendMessage(Translation::RED . "You must have " . $rankRequired->getColoredName() . TextFormat::RESET . TextFormat::GRAY . " Rank or up to use this command!");
            return;
        }
        if(!PlotManager::isPlotWorld($sender->getWorld())) {
            if($sender->getDataSession()->getRank()->getIdentifier() < Rank::TRAINEE or $sender->getDataSession()->getRank()->getIdentifier() > Rank::EXECUTIVE) {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
        }
        if($sender->getAllowFlight() === true) {
            $sender->setAllowFlight(false);
            $sender->setFlying(false);
        }
        else {
            $sender->setAllowFlight(true);
            $sender->setFlying(true);
        }
        $sender->sendMessage(Translation::getMessage("flightToggle"));
    }
}