<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class PingCommand extends Command {

    /**
     * PingCommand constructor.
     */
    public function __construct() {
        parent::__construct("ping", "Check ping.", "/ping [player]");
        $this->registerArgument(0, new TargetArgument("player", true));
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
        if(isset($args[0])) {
            $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
            if($player === null) {
                $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                return;
            }
        }
        if(isset($player)) {
            $ping = $player->getNetworkSession()->getPing();
            $name = $player->getName() . "'s";
        }
        else {
            $ping = $sender->getNetworkSession()->getPing();
            $name = "Your";
        }
        if($ping === null) {
            $ping = -1;
        }
        $sender->sendMessage(TextFormat::DARK_RED . "$name ping: " .  TextFormat::WHITE . "$ping milliseconds");
    }
}