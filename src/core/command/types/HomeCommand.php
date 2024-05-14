<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\task\TeleportTask;
use core\command\utils\args\RawStringArgument;
use core\command\utils\Command;
use core\level\LevelManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class HomeCommand extends Command {

    /**
     * HomeCommand constructor.
     */
    public function __construct() {
        parent::__construct("home", "Teleport to a home", "/home <name>", ["homes"]);
        $this->registerArgument(0, new RawStringArgument("name"));
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
            $world = $sender->getWorld()->getFolderName();
            $setup = LevelManager::getSetup();
            if($world === $setup->getNested("boss.world") || $world === $setup->getNested("koth.world") || $world === "executive" || $world === "outpost" || $world === "lounge"){
                $sender->sendMessage(Translation::getMessage("cantTeleportToHomeHere"));
                return;
            }
            if($commandLabel === "homes") {
                $sender->sendMessage(TextFormat::DARK_RED . TextFormat::BOLD . "(!) Homes:");
                $sender->sendMessage(TextFormat::WHITE . implode(", ", array_keys($sender->getDataSession()->getHomes())));
                return;
            }
            if(isset($args[0])) {
                $home = $sender->getDataSession()->getHome($args[0]);
                if($home === null) {
                    $sender->sendMessage(TextFormat::DARK_RED . TextFormat::BOLD . "(!) Homes:");
                    $sender->sendMessage(TextFormat::WHITE . implode(", ", array_keys($sender->getDataSession()->getHomes())));
                    return;
                }
                $world = $home->getWorld()->getFolderName();
                if($world === $setup->getNested("boss.world") || $world === $setup->getNested("koth.world") || $world === "executive" || $world === "outpost" || $world === "lounge"){
                    $sender->sendMessage(Translation::getMessage("cantTeleportToHome"));
                    return;
                }
                $this->getCore()->getScheduler()->scheduleRepeatingTask(new TeleportTask($sender, $home, 10), 20);
                return;
            }
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}