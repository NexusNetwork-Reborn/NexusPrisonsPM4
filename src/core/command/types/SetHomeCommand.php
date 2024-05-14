<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\RawStringArgument;
use core\command\utils\Command;
use core\level\LevelManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;

class SetHomeCommand extends Command {

    /**
     * SetHomeCommand constructor.
     */
    public function __construct() {
        parent::__construct("sethome", "Set a home", "/sethome <name>");
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
            if(count($sender->getDataSession()->getHomes()) >= $sender->getDataSession()->getRank()->getHomeLimit($sender)) {
                $sender->sendMessage(Translation::getMessage("maxReached"));
                return;
            }
            if($sender->getGamemode()->id() === GameMode::SPECTATOR()->id()) {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            if($sender->hasVanished()) {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            $world = $sender->getWorld()->getFolderName();
            $setup = LevelManager::getSetup();
            if($world === $setup->getNested("boss.world") || $world === $setup->getNested("koth.world") || $world === "executive" || $world === "outpost" || $world === "lounge"){
                $sender->sendMessage(Translation::getMessage("cantSetHomeHere"));
                return;
            }
            if(isset($args[0])) {
                $home = $sender->getDataSession()->getHome($args[0]);
                if($home !== null) {
                    $sender->sendMessage(Translation::getMessage("homeExist"));
                    return;
                }
                $sender->sendMessage(Translation::getMessage("setHome"));
                $sender->getDataSession()->addHome($args[0], $sender->getPosition());
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