<?php

declare(strict_types=1);

namespace core\command\types;

use core\command\utils\args\RawStringArgument;
use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\utils\TextFormat;

class SpectateCommand extends Command {

    /**
     * SpectateCommand constructor.
     */
    public function __construct() {
        parent::__construct("spectate", "Spectate a player.", "/spectate <on/off/rand> [player]");
        $this->registerArgument(0, new RawStringArgument("on/off/rand"));
        $this->registerArgument(1, new TargetArgument("player"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if ($sender instanceof NexusPlayer) {
            if (isset($args[0])) {
                if (!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    if (!$sender->hasPermission("permission.staff")) {
                        $sender->sendMessage(Translation::getMessage("noPermission"));
                        return;
                    }
                }
                switch ($args[0]) {
                    case "on":
                        if (!isset($args[1])) {
                            $sender->sendMessage(Translation::getMessage("usageMessage", [
                                "usage" => $this->getUsage()
                            ]));
                            return;
                        }
                        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[1]);
                        if ($player === null) {
                            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                            return;
                        }
                        $sender->teleport($player->getPosition());
                        $sender->setGamemode(GameMode::SPECTATOR());
                        $sender->setInvisible(true);
                        foreach ($sender->getViewers() as $viewer) {
                            if ($viewer->getServer()->isOp($viewer->getName())) {
                                continue;
                            }

                            $viewer->hidePlayer($sender);
                        }

                        $sender->sendMessage(Translation::getMessage("coordsShowChange", [
                            "mode" => $args[0]
                        ]));
                        break;
                    case "rand":
                        $players = $this->getCore()->getServer()->getOnlinePlayers();
                        $player = $players[array_rand($players)];
                        if ($player->getUniqueId()->toString() === $sender->getUniqueId()->toString()) {
                            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                            return;
                        }
                        $sender->teleport($player->getPosition());
                        $sender->setGamemode(GameMode::SPECTATOR());
                        $sender->setInvisible(true);
                        foreach ($sender->getViewers() as $viewer) {
                            if ($viewer->getServer()->isOp($viewer->getName())) {
                                continue;
                            }

                            $viewer->hidePlayer($sender);
                        }

                        $sender->sendMessage(Translation::getMessage("spectating", [
                            "player" => TextFormat::GOLD . $player->getName()
                        ]));
                        $sender->sendMessage(Translation::getMessage("coordsShowChange", [
                            "mode" => $args[0]
                        ]));
                        break;
                    case "off":
                        //if($sender->getGamemode()->id() === GameMode::SPECTATOR()->id()) {
                        $sender->setInvisible(false);
                        foreach ($sender->getViewers() as $viewer) {
                            if ($viewer->getServer()->isOp($viewer->getName())) {
                                continue;
                            }

                            $viewer->showPlayer($sender);
                        }

                        $sender->setGamemode(GameMode::SURVIVAL());
                        $sender->teleport($this->getCore()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                        // }
                        break;
                }
                return;
            }
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
        return;
    }
}