<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class BoostsCommand extends Command {

    /**
     * BoostsCommand constructor.
     */
    public function __construct() {
        parent::__construct("boosts", "List all your boosts.", "/boosts", ["xpboost", "energyboost"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if($sender->getDataSession()->getXPBoostTimeLeft() > 0) {
            $modifier = $sender->getDataSession()->getBaseXPModifier();
            $sender->sendMessage(Translation::ORANGE . "You have an active " . number_format($modifier, 2) . "x XP Booster for: " . TextFormat::GREEN . Utils::secondsToTime($sender->getDataSession()->getXPBoostTimeLeft()));
        }
        else {
            $sender->sendMessage(Translation::ORANGE . "You have no ongoing XP Boosters.");
        }
        if($sender->getDataSession()->getEnergyBoostTimeLeft() > 0) {
            $modifier = $sender->getDataSession()->getEnergyModifier();
            $sender->sendMessage(Translation::ORANGE . "You have an active " . number_format($modifier, 2) . "x Energy Booster for: " . TextFormat::GREEN . Utils::secondsToTime($sender->getDataSession()->getEnergyBoostTimeLeft()));
        }
        else {
            $sender->sendMessage(Translation::ORANGE . "You have no ongoing Energy Boosters.");
        }
        if($sender->getDataSession()->getExecutiveBoostTimeLeft() > 0) {
            $sender->sendMessage(Translation::ORANGE . "You have an active Executive Booster for: " . TextFormat::GREEN . Utils::secondsToTime($sender->getDataSession()->getExecutiveBoostTimeLeft()));
        }
        else {
            $sender->sendMessage(Translation::ORANGE . "You have no ongoing Executive Boosters.");
        }
    }
}