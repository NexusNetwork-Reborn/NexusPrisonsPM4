<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\task\TeleportTask;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class SpawnCommand extends Command {

    /**
     * SpawnCommand constructor.
     */
    public function __construct() {
        parent::__construct("spawn", "Teleport to spawn.", "/spawn");
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
            $spawn = $level->getSpawnLocation();
            $this->getCore()->getScheduler()->scheduleRepeatingTask(new TeleportTask($sender, $spawn, 10), 20);
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
        return;
    }
}