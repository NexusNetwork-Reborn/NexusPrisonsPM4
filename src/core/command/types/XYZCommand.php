<?php

declare(strict_types = 1);

namespace core\command\types;

use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;

class XYZCommand extends Command {

    /**
     * XYZCommand constructor.
     */
    public function __construct() {
        parent::__construct("xyz", "Show your coordinates.", "/xyz <on/off>", ["coords"]);
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
        if(!isset($args[0])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        switch($args[0]) {
            case "on":
                $pk = new GameRulesChangedPacket();
                $pk->gameRules = [
                    "showcoordinates" => new BoolGameRule(true, false)
                ];
                $sender->getNetworkSession()->sendDataPacket($pk);
                $sender->sendMessage(Translation::getMessage("coordsShowChange", [
                    "mode" => $args[0]
                ]));
                return;
            case "off":
                $pk = new GameRulesChangedPacket();
                $pk->gameRules = [
                    "showcoordinates" => new BoolGameRule(false, false)
                ];
                $sender->getNetworkSession()->sendDataPacket($pk);
                $sender->sendMessage(Translation::getMessage("coordsShowChange", [
                    "mode" => $args[0]
                ]));
                return;
            default:
                $sender->sendMessage(Translation::getMessage("usageMessage", [
                    "usage" => $this->getUsage()
                ]));
                return;
        }
    }
}