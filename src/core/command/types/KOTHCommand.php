<?php

declare(strict_types = 1);

namespace core\command\types;

use core\game\combat\koth\task\StartKOTHGameTask;
use core\command\task\TeleportTask;
use core\command\utils\Command;
use core\level\LevelManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\world\Position;
use pocketmine\permission\DefaultPermissions;

class KOTHCommand extends Command {

    /**
     * KOTHCommand constructor.
     */
    public function __construct() {
        parent::__construct("koth", "Start a KOTH game/Teleport to KOTH");
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
            $kothManager = $this->getCore()->getGameManager()->getCombatManager();
            if($kothManager->getKOTHGame() !== null) {
                $level = $sender->getServer()->getWorldManager()->getWorldByName(LevelManager::getSetup()->getNested("koth.world"));
                $positions = [];
                foreach (LevelManager::getSetup()->getNested("koth.player-spawn") as $pos) {
                    $v = explode(":", $pos);
                    $positions[] = new Position((float)$v[0], (float)$v[1], (float)$v[2], $level);
                }
                $this->getCore()->getScheduler()->scheduleRepeatingTask(new TeleportTask($sender, $positions[array_rand($positions)], 5), 20);
                $sender->sendMessage(Translation::getMessage("kothJoined"));
                //$this->getCore()->getScheduler()->scheduleRepeatingTask(new TeleportTask($sender, new Position(-17, 22, 90, $level), 5), 20);
                return;
            } else {
                $sender->sendMessage(Translation::getMessage("kothNotRunning"));
            }
        }
//        if($sender->hasPermission(DefaultPermissions::ROOT_OPERATOR) or $sender instanceof ConsoleCommandSender or $sender->hasPermission("permission.admin")) {
//            $kothManager = $this->getCore()->getGameManager()->getCombatManager();
//            if($kothManager->getKOTHGame() !== null) {
//                $sender->sendMessage(Translation::getMessage("kothRunning"));
//                return;
//            }
//            $kothManager->initiateKOTHGame();
//            $this->getCore()->getScheduler()->scheduleRepeatingTask(new StartKOTHGameTask($this->getCore()), 20);
//            return;
//        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}