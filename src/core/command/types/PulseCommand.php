<?php

namespace core\command\types;

use core\command\utils\Command;
use core\level\LevelManager;
use core\level\tile\Meteorite;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class PulseCommand extends Command {

    /**
     * NearCommand constructor.
     */
    public function __construct() {
        parent::__construct("pulse", "Check for nearby meteorites");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender instanceof NexusPlayer) or (!$sender->hasPermission("permission.tier1"))) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            $rankRequired = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::NOBLE);
            $sender->sendMessage(Translation::RED . "You must have " . $rankRequired->getColoredName() . TextFormat::RESET . TextFormat::GRAY . " Rank or up to use this command!");
            return;
        }
        $sender->sendMessage(TextFormat::GOLD . "Nearby meteorites within " . TextFormat::BOLD . TextFormat::AQUA . "128m");
        $bb = $sender->getBoundingBox()->expandedCopy(128, 128, 128);
        $world = $sender->getWorld();
        if($world === null) {
            return;
        }
        $nearby = [];
        foreach(LevelManager::getNearbyTiles($world, $bb) as $t) {
            if($t instanceof Meteorite) {
                $nearby[] = $t;
            }
        }
        foreach($nearby as $meteorite) {
            if($meteorite->isClosed()) {
                continue;
            }
            $name = "Meteorite";
            if($meteorite->isRefined()) {
                $name = "Refined Meteorite";
            }
            $sender->sendMessage(TextFormat::YELLOW . $name . TextFormat::GRAY . " (" . number_format($sender->getLocation()->distance($meteorite->getPosition()), 1) . "m)");
        }
    }
}