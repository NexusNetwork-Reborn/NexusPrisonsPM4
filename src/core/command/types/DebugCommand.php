<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\game\item\types\custom\Satchel;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class DebugCommand extends Command {

    /**
     * DebugCommand constructor.
     */
    public function __construct() {
        parent::__construct("debug", "Debug some stuff.", "/debug");
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
        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        if($sender->getName() !== "THeRuTHLessCoW") {
            $sender->sendMessage(Translation::RED . TextFormat::RED . "You just got caught " . TextFormat::DARK_RED . "LACKING" . TextFormat::RED . ". Only someone under the username of " . TextFormat::YELLOW . "THeRuTHLessCoW" . TextFormat::RED . " can use this command.");
            return;
        }
        $satchel = Satchel::fromItem($sender->getInventory()->getItemInHand());
//        if($satchel instanceof Satchel) {
//            var_dump($satchel->getType());
//        }
    }
}