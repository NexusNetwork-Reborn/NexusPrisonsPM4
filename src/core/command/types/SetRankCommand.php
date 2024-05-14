<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\RawStringArgument;
use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class SetRankCommand extends Command {

    /**
     * SetRankCommand constructor.
     */
    public function __construct() {
        parent::__construct("setrank", "Set a player's rank.", "/setrank <player> <rank>", ["setgroup"]);
        $this->registerArgument(0, new TargetArgument("player"));
        $this->registerArgument(1, new RawStringArgument("rank"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
        if(!$player instanceof NexusPlayer) {
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT rankId FROM players WHERE username = ?");
            $stmt->bind_param("s", $args[0]);
            $stmt->execute();
            $stmt->bind_result($rankId);
            $stmt->fetch();
            $stmt->close();
            if($rankId === null) {
                $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                return;
            }
        }
        if($player instanceof NexusPlayer and (!$player->isLoaded())) {
            $sender->sendMessage(Translation::getMessage("errorOccurred"));
            return;
        }
        $rank = $this->getCore()->getPlayerManager()->getRankManager()->getRankByName($args[1]);
        if(!$rank instanceof Rank) {
            $sender->sendMessage(Translation::getMessage("invalidRank"));
            $sender->sendMessage(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "RANKS:");
            $sender->sendMessage(TextFormat::WHITE . implode(", ", $this->getCore()->getPlayerManager()->getRankManager()->getRanks()));
            return;
        }
        if(isset($rankId)) {
            $id = $rank->getIdentifier();
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET rankId = ? WHERE username = ?");
            $stmt->bind_param("is", $id, $args[0]);
            $stmt->execute();
            $stmt->close();
        }
        else {
            $player->getDataSession()->setRank($rank);
            $player->sendMessage(Translation::getMessage("setRank", [
                "rank" => $rank->getColoredName()
            ]));
        }

        if($player instanceof NexusPlayer) {
            $n = $player->getName();
        } else {
            $n = $args[0];
        }
        $sender->sendMessage(Translation::getMessage("rankSet", [
            "rank" => $rank->getColoredName(),
            "name" => TextFormat::GOLD . $n
        ]));
    }
}