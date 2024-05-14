<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\inventory\TinkerInventory;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class TinkererCommand extends Command {

    /**
     * TinkererCommand constructor.
     */
    public function __construct() {
        parent::__construct("tinkerer", "Open tinker menu", null, ["tinker"]);
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
        if(!Nexus::getInstance()->getServerManager()->getAreaManager()->canDamage($sender->getPosition())) {
            $sender->sendDelayedWindow(new TinkerInventory());
            return;
        }
        $sender->sendTranslatedMessage("inWarzone");
    }
}