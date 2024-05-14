<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\inventory\SeeItemInventory;
use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class LastDeathCommand extends Command {

    /**
     * SeeItemCommand constructor.
     */
    public function __construct() {
        parent::__construct("lastdeath", "See a player's inventory before they died.", "/lastdeath [player]");
        $this->registerArgument(0, new TargetArgument("player"));
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
        $player = $sender;
        if(isset($args[0])) {
            $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
            if(!$player instanceof NexusPlayer) {
                $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                return;
            }
        }
        if(!$player->isLoaded()) {
            $sender->sendMessage(Translation::getMessage("errorOccurred"));
            return;
        }
        $lastDeath = $player->getCESession()->getLastDeath();
        if($lastDeath === null) {
            $sender->sendMessage(Translation::RED . $player->getName() . " hasn't died recently!");
            return;
        }
        $lastDeath->send($sender);
    }
}