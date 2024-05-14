<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class DrinkLimitCommand extends Command {

    /**
     * DrinkLimitCommand constructor.
     */
    public function __construct() {
        parent::__construct("drinklimit", "Check drink limit.", "/drinklimit", ["dl"]);
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
        $dataSession = $sender->getDataSession();
        if($dataSession->getXP() >= (XPUtils::levelToXP(100) + RPGManager::getPrestigeXP($dataSession->getPrestige()))) {
            $sender->sendMessage(Utils::createPrefix(TextFormat::AQUA, "Mining XP Bottle Limit") . TextFormat::RED . "You've reached the max XP capacity!");
            return;
        }
        if($dataSession->getMaxDrinkableXP() <= $dataSession->getXPDrank()) {
            $sender->sendMessage(Utils::createPrefix(TextFormat::AQUA, "Mining XP Bottle Limit") . TextFormat::GREEN . "0 " . TextFormat::GOLD . "Mining XP remaining" . TextFormat::GRAY . "(resets in " . Utils::secondsToTime(86400 - (time() - $dataSession->getDrinkXPTime())) . ")");
            return;
        }
        $sender->sendMessage(Utils::createPrefix(TextFormat::AQUA, "Mining XP Bottle Limit") . TextFormat::GREEN . number_format($dataSession->getMaxDrinkableXP() - $dataSession->getXPDrank()) . " " . TextFormat::GOLD . "Mining XP remaining" . TextFormat::GRAY . "(resets in " . Utils::secondsToTime(86400 - (time() - $dataSession->getDrinkXPTime())) . ")");
    }
}