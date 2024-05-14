<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\task\TeleportTask;
use core\command\utils\Command;
use core\level\LevelManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;

class ShopCommand extends Command {

    /**
     * ShopCommand constructor.
     */
    public function __construct() {
        parent::__construct("shop", "Teleport to shop.", "/shop");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof NexusPlayer) {
            $level = $sender->getServer()->getWorldManager()->getDefaultWorld();
            if($level === null) {
                return;
            }
            $spawn = LevelManager::stringToPosition(LevelManager::getSetup()->getNested("extras.shop-command"), $level);
            //$spawn = new Position(-108, 182, -162, $level);
            $this->getCore()->getScheduler()->scheduleRepeatingTask(new TeleportTask($sender, $spawn, 10), 20);
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}