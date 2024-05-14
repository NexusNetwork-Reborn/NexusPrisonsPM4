<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\Server;

class StopCommand extends Command {

    /**
     * StopCommand constructor.
     */
    public function __construct() {
        parent::__construct("stop", "Restart the server.", "/stop");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof ConsoleCommandSender or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            /** @var NexusPlayer $player */
            foreach(Server::getInstance()->getOnlinePlayers() as $player) {
                if($player->isTagged()) {
                    $player->combatTag(false);
                }
                if(Nexus::getInstance()->getServer()->getPort() === 19123) {
                    //$player->transfer("test.aethic.games", 19132);
                    //continue;
                }
                $player->transfer("play.nexuspe.net", 19132);
            }
            Server::getInstance()->shutdown();
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
        return;
    }
}