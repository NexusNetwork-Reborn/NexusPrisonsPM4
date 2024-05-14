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
use pocketmine\utils\TextFormat;

class SeeItemCommand extends Command {

    /**
     * SeeItemCommand constructor.
     */
    public function __construct() {
        parent::__construct("seeitem", "See a player's [item].", "/seeitem <player>", ["citem"]);
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
        if(!isset($args[0])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
        if(!$player instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        if($player->getItem() === null) {
            $sender->sendMessage(Translation::RED . $player->getName() . " hasn't recently [item] anything!");
            return;
        }
        $inv = new SeeItemInventory($player->getItem());
        $inv->send($sender);
    }
}